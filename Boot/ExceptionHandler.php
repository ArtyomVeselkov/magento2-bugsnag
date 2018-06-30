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
        if ($this->isActive()) {
            $this->prepareCards();
            $this->prepareExclusions();
            $this->registerAllHandlers();
        }
    }


    /**
     * @inheritdoc
     */
    public function isExcluded($errorNo, $errorStr, $errorFile, $errorLine)
    {
        $result = false;
        // Exclude by lines.
        if (isset($this->excludeFiles[$errorFile])) {
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
                /** @var InterfaceVirtualCard $card */
                $lastError = @error_get_last();
                try {
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
                try {
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
            $config = $this->getEarlyBirdConfig();
            if (is_array($config)) {
                try {
                    $client = new BugsnagClient($config);
                    $card = new VirtualCard('Bugsnag M2 Integration - Early Bird', -1, $client);
                    $this->addCard($card);
                } catch (\Exception $exception) {
                    $this->phpLogger->catchException($exception, 'Unable to initialize Bugsnag EarlyBird card.');
                }
            }
        } else {
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
     * Prepare rules for excluding files from being tracked in case of error/exception.
     */
    private function prepareExclusions()
    {
        $exclusions = $this->getExclude();
        if (is_array($exclusions)) {
            $this->excludeFiles = $this->cachedConfig[self::CONFIG_SUBKEY_EXCLUSION];
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
}
