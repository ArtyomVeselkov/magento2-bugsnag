<?php

namespace Optimlight\Bugsnag\Plugin;

use Optimlight\Bugsnag\Boot\ExceptionHandler;
use Optimlight\Bugsnag\Boot\Runner;
use Optimlight\Bugsnag\Helper\Common;

/**
 * Class BeforeHttp
 * @package Optimlight\Bugsnag\Plugin
 */
class BeforeHttp
{
    const CORE_CONFIG_ENABLED = ''; // TODO
    /**
     * @var null|ExceptionHandler
     */
    protected static $handler;
    /**
     * @var bool
     */
    protected static $enabled = false;

    public function __construct(
        Common $helper
    )
    {
        static::$enabled = $dataHelper->getConfigValue(self::CORE_CONFIG_ENABLED, false);
        static::$handler = Runner::getExceptionsHandler();
        Runner::changeReadyState(true);
    }

    /**
     * @param $subject
     */
    public function beforeLaunch($subject)
    {
        if (static::$enabled) {
            $handler = static::$handler;
            if ($handler->isActive()) {
                $handler->prepareCards();
            }
        }
    }
}