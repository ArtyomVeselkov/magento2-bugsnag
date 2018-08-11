<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Optimlight\Bugsnag\Model\Queue\Provider;

use Enqueue\Fs\{FsConnectionFactory, FsContext};
use Interop\Queue\PsrConnectionFactory;

/**
 * Class ContextFs
 * @package Optimlight\Bugsnag\Model\Queue\Profile
 */
class ContextFs extends AbstractContext
{
    /**
     * @var PsrConnectionFactory[]
     */
    private $factories = [];

    /**
     * @param array $options
     * @return FsContext|null
     * @throws \Exception
     */
    public function createContext(array $options)
    {
        $factory = $this->getFactory($options);
        $context = null;
        if ($factory) {
            $context = $factory->createContext();
        }
        return $context;
    }

    /**
     * @param array $options
     * @return FsConnectionFactory|mixed
     * @throws \Exception
     */
    private function getFactory(array $options)
    {
        $hash = $this->getHash($options);
        if (isset($this->factories[$hash])) {
            return $this->factories[$hash];
        } else {
            // For more options @see vendor/enqueue/fs/FsConnectionFactory.php:103
            $factory = new FsConnectionFactory($options);
            if ($factory) {
                $this->factories[$hash] = $factory;
                return $factory;
            } else {
                throw new \Exception('Fs factory cannot be created.');
            }
        }
    }

    /**
     * @param array $options
     * @return string
     */
    private function getHash($options)
    {
        return md5(serialize($options));
    }
}
