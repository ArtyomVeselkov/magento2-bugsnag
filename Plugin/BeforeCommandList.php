<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

namespace Optimlight\Bugsnag\Plugin;

use Optimlight\Bugsnag\Boot\ExceptionHandler;
use Optimlight\Bugsnag\Boot\Runner;
use Optimlight\Bugsnag\Helper\Common;
use Magento\Framework\Console\CommandListInterface;

/**
 * Class BeforeCommandList
 * @package Optimlight\Bugsnag\Plugin
 */
class BeforeCommandList
{
    const CORE_CONFIG_ENABLED = ''; // TODO

    /**
     * @var null|ExceptionHandler
     */
    private static $handler;

    /**
     * @var bool
     */
    private static $enabled = false;

    /**
     * @var int
     */
    private static $calledTimes = 1;

    /**
     * BeforeHttp constructor.
     *
     * @param Common $helper
     */
    public function __construct(
        Common $helper
    ) {
        static::$enabled = $helper->getConfigValue(self::CORE_CONFIG_ENABLED, false);
        static::$handler = Runner::getExceptionsHandler();
        Runner::changeReadyState(true);
    }

    /**
     * @param CommandListInterface $subject
     */
    public function beforeGetCommands($subject)
    {
        if (static::$enabled) {
            $handler = static::$handler;
            if ($handler->isActive()) {
                if (0 < static::$calledTimes--) {
                    $handler->prepareCards();
                    $handler->registerAllHandlers();
                }
            }
        }
    }
}
