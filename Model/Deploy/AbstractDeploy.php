<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Optimlight\Bugsnag\Model\Deploy;

use Optimlight\Bugsnag\Logger\Php as Logger;
use Optimlight\Bugsnag\Helper\CachedFlag;
use Composer\Package\CompletePackageInterface;
use Composer\Composer;

/**
 * Class AbstractDeploy
 * @package Optimlight\Bugsnag\Model\Deploy
 */
abstract class AbstractDeploy implements DeployInterface
{
    /**
     * Time in seconds - how often auto-deploy should be performed.
     */
    const TIME_CHECK_DELAY = 43200;

    /**
     * @var CompletePackageInterface
     */
    private $package = null;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var CachedFlag
     */
    private $cachedFlag;

    /**
     * @var int
     */
    private $timeCheckDelay = self::TIME_CHECK_DELAY;

    /**
     * @var int
     */
    private $currentTime = 0;

    /**
     * Autoloader constructor.
     */
    public function __construct()
    {
        $this->currentTime = time();
        $this->logger = new Logger();
        $this->cachedFlag = new CachedFlag();
    }

    /**
     * @return bool
     */
    private function canDeploy()
    {
        if ($time = $this->cachedFlag->getFlag('deploy_' . static::class)) {
            if ($this->timeCheckDelay < $time - $this->currentTime) {
                return true;
            } else {
                return false;
            }
        }
        return true;
    }

    /**
     *
     */
    private function setDeployStamp()
    {
        $this->cachedFlag->setFlag('deploy_' . static::class, $this->currentTime);
    }

    /**
     * @return bool|string
     */
    private function getCurrentDirectory()
    {
        $path = dirname(__FILE__);
        return $path ? realpath($path) : '';
    }

    /**
     * @return bool
     */
    private function isAppCodePath()
    {
        $path = $this->getCurrentDirectory();
        return 0 === strpos($path, BP . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'code');
    }

    /**
     * @return object
     */
    private function getDefaultPackage()
    {
        $path = $this->getCurrentDirectory();
        $path .= $path ? DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR : '';
        $path .= 'composer.json';
        $result = (new class {
            /**
             * @var array
             */
            private $data = [];

            /**
             * @param string $key
             * @return mixed|null
             */
            public function __get($key)
            {
                return $this->data[$key] ?? null;
            }

            /**
             * @param string $name
             * @param array $arguments
             * @return null
             */
            public function __call($name, $arguments)
            {
                $key = false;
                if (0 === strpos($name, 'get')) {
                    $key = strtolower(substr($name, 3));
                }
                return $key ? $this->{$key} : null;
            }

            /**
             * @param array $data
             */
            public function setData($data)
            {
                $this->data = $data;
            }
        });
        if ($path && \file_exists($path)) {
            $buffer = \file_get_contents($path);
            if ($buffer) {
                $array = \json_decode($buffer, true);
                if (is_array($array)) {
                    $result->setData($array);
                }
            }
        }
        return $result;
    }

    /**
     * @return CompletePackageInterface
     */
    protected function getPackage()
    {
        if (!$this->package) {
            try {
                $io = new \Composer\IO\NullIO();
                /** @var Composer $composer */
                $composer = \Composer\Factory::create($io);
                $this->package = $composer->getRepositoryManager()
                    ->getLocalRepository()
                    ->findPackage('optimlight/magento2-bugsnag', '*');
                // If module is installed into app/code directory.
                if (!$this->package && $this->isAppCodePath()) {
                    $this->package = $this->getDefaultPackage();
                }
            } catch (\Exception $exception) {
                $this->logger->catchException($exception);
            }
        }
        return $this->package;
    }

    /**
     * @return bool
     */
    public function deploy()
    {
        $result = false;
        if (!$this->canDeploy()) {
            $result = true;
        }
        $wasExecuted = $this->exec(!$result);
        if ($wasExecuted) {
            $this->setDeployStamp();
        }
        return $result;
    }

    /**
     * @param bool $canDeploy
     * @return bool
     */
    abstract public function exec($canDeploy = false);
}
