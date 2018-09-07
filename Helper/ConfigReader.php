<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Optimlight\Bugsnag\Helper;

/**
 * Class ConfigReader
 * @package Optimlight\Bugsnag\Helper
 *
 * Probably eventually can be replaced with @see \Magento\Framework\App\DeploymentConfig.
 */
final class ConfigReader
{
    /**
     * First level key for env.php file. Common key.
     */
    const CONFIG_KEY = 'opt_handler';

    /**
     * Second level key for env.php file.
     */
    const CONFIG_SUBKEY_EXCEPTIONS = 'exceptions';

    /**
     * Sub-key for exclusion rules.
     */
    const CONFIG_SUBKEY_EXCLUSION = 'exclude';

    /**
     * Sub-key to define if whole extension is enabled.
     */
    const CONFIG_SUBKEY_ACTIVE = 'active';

    /**
     * Sub-key to define the max number of the same exception to be tracked. Default value is 100. 0 for unlimited
     *   occurrences during single request/execution.
     */
    const CONFIG_SUBKEY_LIMIT = 'limit';

    /**
     * Sub-key for card that would be initialized during composer auto-loading (collecting modules via registration.php).
     */
    const CONFIG_SUBKEY_EARLY_BIRD = 'early_bird';

    /**
     * Sub-key for the file path for internal logging.
     */
    const CONFIG_SUBKEY_DEFAULT_LOG_PATH = 'log_path';

    /**
     * Sub-key for for bool option: should auto-deploy be executed on each request/call (caching mechanism is used).
     * For more info @see \Optimlight\Bugsnag\Boot\Runner::deploy().
     */
    const CONFIG_SUBKEY_AUTO_DEPLOY = 'auto_deploy';

    /**
     * @var array
     */
    private static $config = [];

    /**
     * @return bool|string
     */
    private function getPath()
    {
        return BP . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'env.php';
    }

    /**
     * @return array
     */
    private function getSource()
    {
        if (empty(static::$config)) {
            $path = $this->getPath();
            if (\file_exists($path)) {
                static::$config = require $path;
            } else {
                static::$config = [];
            }
        }
        return static::$config;
    }

    /**
     * @return string
     */
    private function getPrefix()
    {
        return self::CONFIG_KEY . '/' . self::CONFIG_SUBKEY_EXCEPTIONS;
    }

    /**
     * @param string $path
     * @param mixed $default
     * @param bool $fromRoot
     * @return mixed
     */
    public function get($path, $default = null, $fromRoot = false)
    {
        $result = $default;
        $source = $this->getSource();
        if (is_array($source)) {
            $foundFlag = true;
            $parts = explode('/', $path);
            $buffer = $fromRoot ? $source : $this->get($this->getPrefix(), [], true);
            foreach ($parts as $part) {
                if (is_array($buffer) && isset($buffer[$part])) {
                    $buffer = $buffer[$part];
                } else {
                    $foundFlag = false;
                    break;
                }
            }
            $result = $foundFlag ? $buffer : $result;
        }
        return $result;
    }
}
