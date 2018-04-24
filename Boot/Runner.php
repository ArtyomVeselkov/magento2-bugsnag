<?php

/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

namespace Optimlight\Bugsnag\Boot;

/**
 * Class Runner
 * @package Optimlight\Bugsnag\Model
 */
class Runner
{
    /**
     * @var array
     */
    protected static $magentoConfiguration = null;

    /**
     * @var null|ExceptionHandler
     */
    protected static $exceptionsHandler = null;

    /**
     * @var bool
     */
    protected static $magentoReadyFlag = false;

    /**
     * @var null|\Magento\Customer\Model\Session
     */
    public static $customerSession = null;

    /**
     * 
     */
    public static function init()
    {
        try {
            // Get array from app/etc/env.php.
            if (static::loadMagentoConfig()) {
                static::initHandler();
            }
        } catch (\Exception $exception) {}
    }

    /**
     *
     */
    protected static function loadMagentoConfig()
    {
        $path = BP . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'env.php';
        if (file_exists($path)) {
            static::$magentoConfiguration = require $path;
        }
        return is_array(static::$magentoConfiguration);
    }

    /**
     *
     */
    protected static function initHandler()
    {
        $conifg = static::getMagentoConfiguration();
        static::$exceptionsHandler = new ExceptionHandler($conifg ? $conifg : []);
    }

    /**
     * @return array
     */
    public static function getMagentoConfiguration()
    {
        return static::$magentoConfiguration;
    }

    /**
     * @return ExceptionHandler|null
     */
    public static function getExceptionsHandler()
    {
        return static::$exceptionsHandler;
    }

    /**
     * @return \Magento\Customer\Model\Session|null
     */
    public static function getCustomerSession()
    {
        if (static::$magentoReadyFlag) {
            if (is_null(static::$customerSession)) {
                try {
                    // ObjectManager is used as we should not request until some conditions met.
                    $om = \Optimlight\Bugsnag\Helper\Common::getObjectManager();
                    if ($om) {
                        /** @var \Magento\Customer\Model\Session $session */
                        $session = $om->get('Magento\Customer\Model\Session');
                        static::$customerSession = $session;
                    }
                } catch (\Exception $exception) {}
            }
        }
        return static::$customerSession;
    }

    /**
     * @param bool $state
     * @return bool
     */
    public static function changeReadyState($state)
    {
        $previous = static::getReadyState();
        static::$magentoReadyFlag = $state;
        return $previous;
    }

    /**
     * Shows either Magento has been loaded already and it is possible to use it.
     *
     * @return bool
     */
    public static function getReadyState()
    {
        return static::$magentoReadyFlag;
    }
}