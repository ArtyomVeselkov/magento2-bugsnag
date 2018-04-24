<?php

/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

namespace Optimlight\Bugsnag\Model;

/**
 * Class ExceptionHandler
 * @package Optimlight\Bugsnag\Model
 */

class ExceptionHandler
{
    const CONFIG_KEY = 'opterr_handler';
    const CONFIG_SUBKEY_EXCEPTIONS = 'exceptions';
    const CONFIG_SUBKEY_EXCLUSION = 'exclude';
    const CLASS_NAME = __CLASS__;
    
    /**
     * @var 
     */
    protected $previousHandler;
    /**
     * @var array 
     */
    protected $clients = [];
    /**
     * @var bool 
     */
    protected $active = true;
    /**
     * @var array 
     */
    protected $cachedConfig = [];

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
            $this->cachedConfig = $config[self::CONFIG_KEY][self::CONFIG_SUBKEY_EXCEPTIONS];

        } else {
            $this->active = false;
        }
        $this->checkActivation();
        $this->prepareClients();
        $this->prepareExclusions();
    }

    /**
     * 
     */
    public function prepareExclusions()
    {
        if (isset($this->cachedConfig[self::CONFIG_SUBKEY_EXCLUSION]) && is_array($this->cachedConfig[self::CONFIG_SUBKEY_EXCLUSION])) {
            $this->_excludeFiles = $this->cachedConfig[self::CONFIG_SUBKEY_EXCLUSION];
        }
    }

    public function prepareClients()
    {
        // prepare clients
        if (isset($this->cachedConfig['clients']) && is_array($this->cachedConfig['clients'])) {
            $clients = $this->cachedConfig['clients'];
            foreach ($clients as $client => $config) {
                if (isset($this->clientsMap[$client])) {
                    $clientClass = $this->clientsMap[$client];
                } else {
                    $clientClass = $client;
                }
                $buffer = null;
                if (class_exists($clientClass) && is_array($config)) {
                    try {
                        $buffer = new $clientClass($config);
                        if ($buffer && is_a($buffer, 'Optimlight\\Bugsnag\\Client\\AbstractClient')) {
                            $this->_clients[] = $buffer;
                        }
                    } catch (\Exception $exception) {}
                }
            }
        }
    }

    /**
     * @return bool
     */
    public function checkActivation()
    {
        if (!isset($this->cachedConfig['active']) || !$this->cachedConfig['active']) {
            $this->active = false;
        }
        if ($this->active) {
            $this->registerHandler();
        }
        return $this->active;
    }

    public function isExcluded($errorNo, $errorStr, $errorFile, $errorLine)
    {
        $result = false;
        // exclude by lines
        if (isset($this->_excludeFiles[$errorFile])) {
            $lines = $this->_excludeFiles[$errorFile];
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
                $this->_previousHandler = $buffer;
            }
            // here is workaround for strange not settings error_handler for the first time
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
            foreach ($this->_clients as $client) {
                /** @var \Optimlight\Bugsnag\Model\Client\AbstracClient $client */
                $lastError = @error_get_last();
                $client->execute($errorNo, $errorStr, $errorFile, $errorLine, $lastError);
            }
        }
        if (is_array($this->_previousHandler) && count($this->_previousHandler)) {
            if (!is_a(@$this->_previousHandler[0], self::CLASS_NAME)) {
                $result = call_user_func_array($this->_previousHandler, [$errorNo, $errorStr, $errorFile, $errorLine]);
            }
        }
        return $result;
    }
}