<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

namespace Optimlight\Bugsnag\Boot;

use Magento\Customer\Model\Session;
use Optimlight\Bugsnag\Logger\Php as Logger;
use Optimlight\Bugsnag\Helper\Common as Helper;

/**
 * Class Runner
 * @package Optimlight\Bugsnag\Model
 *
 * Class responsible for holding cards and managing exceptions handlers used by Bugsnag client(s).
 * As it runs before Magento Framework ut couldn't relay on its infrastructure.
 */
final class Runner
{
    /**
     * Holds content of env.php file.
     *
     * @var array|null
     */
    private static $magentoConfiguration = null;

    /**
     * Exceptions handler.
     *
     * @var ExceptionHandler
     */
    private static $exceptionsHandler = null;

    /**
     * PHP native logger.
     *
     * @var Logger
     */
    private static $phpLogger;

    /**
     * Indicates that Magento's Framework is ready and that we can try get customer's session.
     *
     * @var bool
     */
    private static $magentoReadyFlag = false;

    /**
     * Customer's session (if present).
     *
     * @var Session
     */
    public static $customerSession = null;

    /**
     * Main entry point.
     */
    public static function init()
    {
        static::$phpLogger = new Logger();
        try {
            // Get array from app/etc/env.php.
            if (static::getEnvConfiguration()) {
                static::initHandler();
            }
        } catch (\Exception $exception) {
            static::$phpLogger->catchException($exception);
        }
    }


    /**
     * Get initialized exceptions handler.
     *
     * @return ExceptionHandler|null
     */
    public static function getExceptionsHandler()
    {
        return static::$exceptionsHandler;
    }

    /**
     * In case of Magento being loaded -- return current customer's session.
     *
     * @return Session|null
     */
    public static function getCustomerSession()
    {
        if (static::$magentoReadyFlag) {
            if (is_null(static::$customerSession)) {
                try {
                    // ObjectManager is used as we should not request until some conditions met.
                    $om = Helper::getObjectManager();
                    if ($om && static::isAreaSet()) {
                        /** @var \Magento\Customer\Model\Session $session */
                        $session = $om->get('Magento\Customer\Model\Session');
                        static::$customerSession = $session;
                    }
                } catch (\Exception $exception) {
                    static::$phpLogger->catchException($exception);
                }
            }
        }
        return static::$customerSession;
    }

    /**
     * @return bool
     */
    public static function isAreaSet()
    {
        if (static::$magentoReadyFlag) {
            try {
                $om = Helper::getObjectManager();
                // We cannot use \Magento\Framework\App\State as it will throw exception in case of not set areaCode.
                /** @var \Magento\Framework\Config\ScopeInterface $scope */
                $scope = $om->get('Magento\Framework\Config\ScopeInterface');;
                $value = $scope->getCurrentScope();
                return !(!$value || 'primary' === $value);
            } catch (\Exception $exception) {
                static::$phpLogger->catchException($exception);
            }
        }
        return false;
    }

    /**
     * Change the ready state.
     * By default is switch to "true" on HTTP request processing.
     *
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

    /**
     * Returns true if $magentoConfiguration is populated.
     *
     * @return bool
     * @throws \Exception
     */
    private static function getEnvConfiguration()
    {
        $path = BP . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'env.php';
        if (file_exists($path)) {
            static::$magentoConfiguration = require $path;
        } else {
            throw new \Exception(sprintf('Env file "%s" doesn\'t exists.', $path));
        }
        return is_array(static::$magentoConfiguration);
    }

    /**
     * Initialize custom exceptions handler (also handles Magento native).
     */
    private static function initHandler()
    {
        $config = static::getMagentoConfiguration();
        static::deploy();
        static::$exceptionsHandler = new ExceptionHandler($config ?? []);
    }

    /**
     * Run deploy commands. Full NS for classes/interfaces should be left "as is".
     */
    private static function deploy()
    {
        $map = [\Optimlight\Bugsnag\Model\Deploy\Autoloader::class];
        foreach ($map as $class) {
            if (\class_exists($class, true)) {
                $instance = new $class();
                if (is_a($instance, \Optimlight\Bugsnag\Model\Deploy\DeployInterface::class)) {
                    /** @var \Optimlight\Bugsnag\Model\Deploy\DeployInterface $instance */
                    $instance->deploy();
                }
            }
        }
    }

    /**
     * Get loaded array from env.php file.
     *
     * @return array
     */
    public static function getMagentoConfiguration()
    {
        return static::$magentoConfiguration;
    }
}
