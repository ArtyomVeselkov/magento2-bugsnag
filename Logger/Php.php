<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Optimlight\Bugsnag\Logger;

use Optimlight\Bugsnag\Helper\ConfigReader as Config;

/**
 * Class Php
 * @package Optimlight\Bugsnag\Logger
 */
class Php implements PhpInterface
{
    /**
     * ID used for syslog messages.
     */
    const SYSLOG_ID = 'bugsnag_php_logger';

    /**
     * @see error_log documentation.
     *
     * @var int
     */
    protected $errorLogType = self::DEFAULT_LOG_TYPE;

    /**
     * @see error_log documentation.
     *
     * @var string
     */
    protected $errorLogDestination = self::DEFAULT_LOG_DESTINATION;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var bool
     */
    protected $debug = false;

    /**
     * @var bool
     */
    protected static $syslog = false;

    /**
     * Php constructor.
     * @param int $errorLogType
     * @param string $errorLogDestination
     */
    public function __construct($errorLogType = null, $errorLogDestination = null)
    {
        $this->overwriteDefaults();
        $this->config = new Config();
        $this->errorLogType = $errorLogType ?? $this->errorLogType;
        $this->errorLogDestination = $errorLogDestination ?? $this->errorLogDestination;
        $this->debug = 1 < $this->config->get('debug', 0);
    }

    /**
     *
     */
    private function overwriteDefaults()
    {
        $path = $this->config->get(Config::CONFIG_SUBKEY_DEFAULT_LOG_PATH, false);
        if (is_string($path) && strlen($path)) {
            $this->errorLogDestination = $path;
            $this->errorLogType = 3;
        }
    }

    /**
     *
     */
    private function initSyslog()
    {
        if (!static::$syslog && $this->debug) {
            // Disable logging in case of not initialized syslog.
            if ($this->debug = openlog(self::SYSLOG_ID, LOG_ODELAY | LOG_PID, LOG_LOCAL0)) {
                register_shutdown_function(function () {
                    closelog();
                });
            }
            static::$syslog = true;
        }
    }

    /**
     * @inheritdoc
     */
    public function catchException(\Exception $exception, $additionalMessage = '')
    {
        if (strlen(trim($additionalMessage))) {
            $additionalMessage = sprintf('[%s] ', $additionalMessage);
        }
        try {
            error_log($additionalMessage . $exception->getMessage(), $this->errorLogType, $this->errorLogDestination);
        } catch (\Exception $exception) {
            if (0 != $this->errorLogType) {
                error_log($additionalMessage . $exception->getMessage(), 0);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function debug($message)
    {
        if (!$this->debug) {
            return ;
        }
        $this->initSyslog();
        syslog(LOG_DEBUG, $message);
    }
}
