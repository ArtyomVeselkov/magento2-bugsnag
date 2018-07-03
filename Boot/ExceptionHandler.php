<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

namespace Optimlight\Bugsnag\Boot;

use Optimlight\Bugsnag\Logger\Php as Logger;
use Optimlight\Bugsnag\Model\VirtualCard;
use Optimlight\Bugsnag\Model\InterfaceVirtualCard;
use Optimlight\Bugsnag\Helper\VirtualClass;
use Optimlight\Bugsnag\Model\Client\Bugsnag as BugsnagClient;
use Optimlight\Bugsnag\Model\Resolver\Build\BuildInterface;
use Magento\Framework\DataObject;

/**
 * Class ExceptionHandler
 * @package Optimlight\Bugsnag\Model
 */

/**
 * @method getExceptions()
 * @method getExclude()
 * @method getActive()
 * @method getEarlyBird()
 */

final class ExceptionHandler extends DataObject implements ExceptionHandlerInterface
{
    /**
     * Array for registered PHP error/exceptions handlers.
     *
     * @var array
     */
    private $previousHandlers = [];

    /**
     * Collected virtual cards for current PHP run.
     *
     * @var InterfaceVirtualCard[]
     */
    private $cards = [];

    /**
     * Files to be excluded from being tracked.
     *
     * @var string[]
     */
    private $excludeFiles = [];

    /**
     * Extensions configuration read from env.php file.
     *
     * @var array
     */
    private $cachedConfig = [];

    /**
     * Logger (not Monolog).
     *
     * @var
     */
    private $phpLogger;

    /**
     * @var bool
     */
    private $birdFlewOut = false;

    /**
     * @var bool
     */
    private static $shutdownFlag = false;

    /**
     * ExceptionHandler constructor.
     *
     * @param array $config
     * @param array $data
     */
    public function __construct(array $config, array $data = [])
    {
        $this->phpLogger = new Logger();
        parent::__construct($data);
        // Be patient and check all.
        if (
            isset($config[self::CONFIG_KEY]) &&
            isset($config[self::CONFIG_KEY][self::CONFIG_SUBKEY_EXCEPTIONS]) &&
            is_array($config[self::CONFIG_KEY][self::CONFIG_SUBKEY_EXCEPTIONS]) &&
            count($config[self::CONFIG_KEY][self::CONFIG_SUBKEY_EXCEPTIONS])
        ) {
            $this->setData($config[self::CONFIG_KEY][self::CONFIG_SUBKEY_EXCEPTIONS]);
        }
        if ($this->isActive() && $this->canStart($config)) {
            $this->prepareCards();
            $this->prepareExclusions();
            $this->registerAllHandlers();
        }
    }

    /**
     * Additional validation before start.
     *
     * @param $config
     *
     * @return bool
     */
    public function canStart($config)
    {
        if (isset($config['db']['connection']['default']['password']) && isset($config['db']['connection']['default']['dbname'])) {
            return false;
        }
        if (PHP_SAPI == 'cli' && isset($_SERVER) && isset($_SERVER['argv']) && in_array($_SERVER['argv'], ['setup:install'])) {
            return false;
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function isExcluded($errorNo, $errorStr, $errorFile, $errorLine)
    {
        $result = false;
        // Exclude by lines.
        if (isset($this->excludeFiles[$errorFile]) || $this->extendedFileMath($errorFile)) {
            $lines = $this->excludeFiles[$errorFile];
            if (is_array($lines)) {
                foreach ($lines as $lineRange) {
                    if (is_array($lineRange) && count($lineRange)) {
                        $from = $lineRange[0];
                        if (2 == count($lineRange)) {
                            $to = $lineRange[1];
                        } else {
                            $to = $from;
                        }
                        if ($errorLine >= $from && $errorLine <= $to) {
                            $result = true;
                        }
                    }
                }
            }
        }
        return $result;
    }

    /**
     * @param string $errorFile
     * @return bool
     */
    private function extendedFileMath(&$errorFile)
    {
        if (strlen($errorFile)) {
            foreach ($this->excludeFiles as $file => $rules) {
                if (':' === $file[0]) {
                    $file = substr($file, 1);
                    if (fnmatch($file, $errorFile)) {
                        $errorFile = ':' . $file;
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    public function registerHandler($name, $method, $setHandler)
    {
        $buffer = [null];
        $limit = 3;
        while (
            !is_null($buffer) &&
            (
                is_array($buffer) &&
                !is_a($buffer[0], static::class)
            ) &&
            $limit > 0
        ) {
            $buffer = call_user_func_array($setHandler, [[$this, $method]]);
            if (
                is_array($buffer) &&
                count($buffer) &&
                !is_a($buffer[0], static::class)
            ) {
                $this->previousHandlers[$name] = $buffer;
            }
            // Here is workaround for strange not settings error_handler for the first time.
            $buffer = call_user_func_array($setHandler, [[$this, $method]]);
            $limit--;
        }
    }

    /**
     * @inheritdoc
     */
    public function registerAllHandlers()
    {
        $this->registerErrorHandler();
        $this->registerExceptionHandler();
        $this->registerShutdownFunction();
    }

    /**
     * @inheritdoc
     */
    public function customHandleError($errorNo, $errorStr, $errorFile, $errorLine)
    {
        $result = false;
        // If current exception (by file/line) is not excluded -- process it.
        if (!$this->isExcluded($errorNo, $errorStr, $errorFile, $errorLine)) {
            foreach ($this->cards as $card) {
                // Skip non-PHP cards.
                if (InterfaceVirtualCard::TYPE_PHP !== $card->getType()) {
                    continue;
                }
                // Here is a hack. In some cases (for example unexciting class) $errorNo can be "0", that's why set it
                // to "1".
                $errorNo = 0 === $errorNo ? \E_ERROR : $errorNo;
                /** @var InterfaceVirtualCard $card */
                $lastError = @error_get_last();
                try {
                    // If you see this line in Bugsnag, then there is no trace available for this error.
                    $card->execute($errorNo, $errorStr, $errorFile, $errorLine, $lastError);
                } catch (\Exception $exception) {
                    $this->phpLogger->catchException(
                        $exception,
                        'Unable to process error with Bugsnag card: ' . $card->getName()
                    );
                }
            }
        }
        // If there is a fallback handler -- process it also.
        if (
            isset($this->previousHandlers[self::HANDLER_ERROR]) &&
            is_array($this->previousHandlers[self::HANDLER_ERROR]) &&
            count($this->previousHandlers[self::HANDLER_ERROR])
        ) {
            // Prevent recursion.
            if (!is_a(@$this->previousHandlers[self::HANDLER_ERROR], static::class)) {
                $result = call_user_func_array(
                    $this->previousHandlers[self::HANDLER_ERROR],
                    [$errorNo, $errorStr, $errorFile, $errorLine]
                );
            }
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function customHandleException($exception)
    {
        $result = false;
        $errorNo = $exception->getCode();
        $errorStr = $exception->getMessage();
        $errorFile = $exception->getFile();
        $errorLine = $exception->getLine();
        // If current exception (by file/line) is not excluded -- process it.
        if (!$this->isExcluded($errorNo, $errorStr, $errorFile, $errorLine)) {
            foreach ($this->cards as $card) {
                // Skip non-PHP cards.
                if (InterfaceVirtualCard::TYPE_PHP !== $card->getType()) {
                    continue;
                }
                /** @var \Optimlight\Bugsnag\Model\InterfaceVirtualCard $card */
                $lastError = @error_get_last();
                // Here is a hack. In some cases (for example unexciting class) $errorNo can be "0", that's why set it
                // to "1".
                $errorNo = 0 === $errorNo ? \E_ERROR : $errorNo;
                try {
                    // If you see this line in Bugsnag, then there is no trace available for this exception.
                    $card->execute($errorNo, $errorStr, $errorFile, $errorLine, $lastError);
                } catch (\Exception $e) {
                    $this->phpLogger->catchException(
                        $exception,
                        'Unable to process exception with Bugsnag card: ' . $card->getName()
                    );
                }
            }
        }
        // If there is a fallback handler -- process it also.
        if (
            isset($this->previousHandlers[self::HANDLER_EXCEPTION]) &&
            is_array($this->previousHandlers[self::HANDLER_EXCEPTION]) &&
            count($this->previousHandlers[self::HANDLER_EXCEPTION])
        ) {
            // Prevent recursion.
            if (!is_a(@$this->previousHandlers[self::HANDLER_EXCEPTION], static::class)) {
                $result = call_user_func_array(
                    $this->previousHandlers[self::HANDLER_EXCEPTION],
                    [$errorNo, $errorStr, $errorFile, $errorLine]
                );
            }
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getCards()
    {
        return $this->cards;
    }

    /**
     * @inheritdoc
     */
    public function isActive()
    {
        return $this->getActive();
    }

    /**
     * Get configuration for EarlyBird card.
     * There could be only one EarlyBird card.
     *
     * @return array
     */
    private function getEarlyBirdConfig()
    {
        $result = [];
        $buffer = $this->getEarlyBird();
        if (is_array($buffer)) {
            $result = $buffer;
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function prepareCards()
    {
        // TODO Probably change logic of repeating execution of this method.
        // As Magento was not started yet, we create first card "manually".
        if (!Runner::getReadyState()) {
            $this->prepareEarlyBird();
        } else {
            $this->prepareRegularCards();
        }
    }

    /**
     * @inheritdoc
     */
    public function addCard(InterfaceVirtualCard $card, $overwrite = false)
    {
        if ($card->validate()) {
            $id = $card->getId();
            if ($overwrite || !isset($this->cards[$id])) {
                $this->cards[$id] = $card;
            }
        }
    }

    /**
     *
     */
    private function prepareRegularCards()
    {
        try {
            // Try load cards only after Magento is loaded.
            $om = \Optimlight\Bugsnag\Helper\Common::getObjectManager();
            if ($om) {
                /** @var VirtualClass $helper */
                $helper = $om->get(VirtualClass::class);
                $cards = $helper->filterVirtualTypes(self::VIRTUAL_CARD_TYPE_PREFIX);
                $helper->initVirtualTypes($cards, []);
                foreach ($cards as $card) {
                    $this->addCard($card);
                }
            }
        } catch (\Exception $exception) {
            $this->phpLogger->catchException($exception, 'Unable to initialize Bugsnag cards.');
        }
    }

    /**
     * Create card for early bird manually.
     */
    private function prepareEarlyBird()
    {
        $config = $this->getEarlyBirdConfig();
        if (is_array($config) && !$this->birdFlewOut) {
            try {
                $build = $config['build_class'] ?? false;
                $buildConfig = $config['build_config'] ?? false;
                if (
                    $build && is_array($buildConfig) && class_exists($build) &&
                    is_a($build, BuildInterface::class, true)
                ) {
                    $build = $build::getInstance($buildConfig);
                    /** @var BuildInterface $build */
                    $build->resolve();
                } else {
                    $build = null;
                }
                $card = VirtualCard::getInstance(
                    'Bugsnag M2 Integration - Early Bird',
                    -1,
                    null,
                    $build,
                    VirtualCard::TYPE_PHP,
                    true,
                    $config['apikey'] ?? '',
                    $config
                );
                $client = new BugsnagClient($card->getConfig());
                $card->setClient($client);
                $this->addCard($card);
                $this->birdFlewOut = true;
            } catch (\Exception $exception) {
                $this->phpLogger->catchException($exception, 'Unable to initialize Bugsnag EarlyBird card.');
            }
        }
    }

    /**
     * Prepare rules for excluding files from being tracked in case of error/exception.
     */
    private function prepareExclusions()
    {
        $exclusions = $this->getExclude();
        if (is_array($exclusions)) {
            $this->excludeFiles = $exclusions;
        }
    }

    /**
     * Register handler for @see set_error_handler.
     */
    private function registerErrorHandler()
    {
        $this->registerHandler(self::HANDLER_ERROR, 'customHandleError', 'set_error_handler');
    }

    /**
     * Register handler for @see set_exception_handler.
     */
    private function registerExceptionHandler()
    {
        $this->registerHandler(self::HANDLER_EXCEPTION, 'customHandleException', 'set_exception_handler');
    }

    /**
     * Custom listener for the application's shutdown.
     */
    private function registerShutdownFunction()
    {
        register_shutdown_function([$this, 'customShutdownHandler']);
    }

    /**
     * Iterate over all cards.
     */
    public function customShutdownHandler()
    {
        if (static::$shutdownFlag) {
            return ;
        } else {
            static::$shutdownFlag = true;
        }
        foreach ($this->cards as $card) {
            // Skip non-PHP cards.
            if (InterfaceVirtualCard::TYPE_PHP !== $card->getType()) {
                continue;
            }
            /** @var \Optimlight\Bugsnag\Model\InterfaceVirtualCard $card */
            try {
                $card->shutdown();
            } catch (\Exception $exception) {
                $this->phpLogger->catchException(
                    $exception,
                    'Unable to call shutdown method for Bugsnag card: ' . $card->getName()
                );
            }
        }
    }
}
