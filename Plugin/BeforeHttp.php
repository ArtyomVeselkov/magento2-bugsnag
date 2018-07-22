<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
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
    /**
     * @var null|ExceptionHandler
     */
    private static $handler;

    /**
     * @var bool
     */
    private static $enabled = true;

    /**
     * BeforeHttp constructor.
     *
     * @param Common $helper
     */
    public function __construct(
        Common $helper
    ) {
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
                $handler->registerAllHandlers();
            }
        }
    }
}
