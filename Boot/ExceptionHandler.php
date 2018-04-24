<?php

/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

namespace Optimlight\Bugsnag\Boot;

use Magento\Framework\DataObject;
use Optimlight\Bugsnag\Model\Client\Bugsnag as BugsnagClient;
use Optimlight\Bugsnag\Model\VirtualCard as VirtualCard;
use Optimlight\Bugsnag\Model\InterfaceVirtualCard as InterfaceVirtualCard;

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

class ExceptionHandler extends DataObject
{
    const CONFIG_KEY = 'opt_handler';
    const CONFIG_SUBKEY_EXCEPTIONS = 'exceptions';
    const CONFIG_SUBKEY_EXCLUSION = 'exclude';
    const CONFIG_SUBKEY_ACTIVE = 'active';
    const CONFIG_SUBKEY_EARLY_BIRD = 'early_bird';
    const VIRTUAL_CARD_TYPE_PREFIX = 'Optimlight\Bugsnag\Model\Card_';
    const CLASS_NAME = __CLASS__;

    /**
     * @var mixed
     */
    protected $previousHandler;
    /**
     * @var \Optimlight\Bugsnag\InterfaceVirtualCard[]
     */
    protected $cards = [];
    /**
     * @var string[]
     */
    protected $excludeFiles = [];

    /**
     * ExceptionHandler constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        // Be paitent and check all.
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
            $this->registerHandler();
        }
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->getActive();
    }

    /**
     * @return array
     */
    public function getEarlyBirdConfig()
    {
        $result = [];
        $buffer = $this->getEarlyBird();
        if (is_array($buffer)) {
            $result = $buffer;
        }
        return $result;
    }

    /**
     *
     */
    public function prepareCards()
    {
        // As Magento was not started yet, we create first card "manually".
        if (!Runner::getReadyState()) {
            $config = $this->getEarlyBirdConfig();
            if (is_array($config)) {
                $client = new BugsnagClient($config);
                $card = new VirtualCard('Bugsnag M2 Integration - Early Bird', -1, $client);
                $this->addCard($card);
            }
        } else {
            try {
                // Try load cards only after Magento is loaded.
                $om = \Optimlight\Bugsnag\Helper\Common::getObjectManager();
                if ($om) {
                    /** @var \Optimlight\Bugsnag\Helper\VirtualClass $helper */
                    $helper = $om->get('Optimlight\Bugsnag\Helper\VirtualClass');
                    $cards = $helper->filterVirtualTypes(self::VIRTUAL_CARD_TYPE_PREFIX);
                    $helper->initVirtualTypes($cards, []);
                    foreach ($cards as $card) {
                        $this->addCard($card);
                    }
                }
            } catch (\Exception $exception) {
                // TODO
            }
        }
    }

    /**
     * @param InterfaceVirtualCard $card
     */
    protected function addCard(InterfaceVirtualCard $card)
    {
        if ($card->validate()) {
            $id = $card->getId();
            $this->cards[$id] = $card;
        }
    }

    /**
     *
     */
    public function prepareExclusions()
    {
        $exclusions = $this->getExclude();
        if (is_array($exclusions)) {
            $this->excludeFiles = $this->cachedConfig[self::CONFIG_SUBKEY_EXCLUSION];
        }
    }

    /**
     * Check either we should exclude current exception from being logged to the client.
     *
     * @param $errorNo
     * @param $errorStr
     * @param $errorFile
     * @param $errorLine
     * @return bool
     */
    public function isExcluded($errorNo, $errorStr, $errorFile, $errorLine)
    {
        $result = false;
        // exclude by lines
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
     * "Nevalyashka" registration.
     */
    public function registerHandler()
    {
        $buffer = [null];
        $limit = 3;
        while (
            !is_null($buffer) &&
            (
                is_array($buffer) &&
                !is_a($buffer[0], self::CLASS_NAME)
            ) &&
            $limit > 0
        ) {
            $buffer = set_error_handler([$this, 'customHandleError']);
            if (
                is_array($buffer) &&
                count($buffer) &&
                !is_a($buffer[0], self::CLASS_NAME)
            ) {
                $this->previousHandler = $buffer;
            }
            // Here is workaround for strange not settings error_handler for the first time.
            $buffer = set_error_handler([$this, 'customHandleError']);
            $limit--;
        }
    }

    /**
     * Setup custom error handler.
     * Here we check if corresponded script/eval fragment is excluded from the handler.
     * If not -- process it with all registered handlers.
     *
     * @param int $errorNo
     * @param string $errorStr
     * @param string $errorFile
     * @param int $errorLine
     * @return bool
     * @throws \Exception
     */
    public function customHandleError($errorNo, $errorStr, $errorFile, $errorLine)
    {
        $result = false;
        if (!$this->isExcluded($errorNo, $errorStr, $errorFile, $errorLine)) {
            foreach ($this->cards as $card) {
                /** @var \Optimlight\Bugsnag\Model\InterfaceVirtualCard $card */
                $lastError = @error_get_last();
                $card->execute($errorNo, $errorStr, $errorFile, $errorLine, $lastError);
            }
        }
        if (is_array($this->previousHandler) && count($this->previousHandler)) {
            if (!is_a(@$this->previousHandler[0], self::CLASS_NAME)) {
                $result = call_user_func_array($this->previousHandler, [$errorNo, $errorStr, $errorFile, $errorLine]);
            }
        }
        return $result;
    }
}