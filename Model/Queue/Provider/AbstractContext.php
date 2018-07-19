<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Optimlight\Bugsnag\Model\Queue\Provider;

/**
 * Class AbstractContext
 * @package Optimlight\Bugsnag\Model\Queue\Provider
 */
abstract class AbstractContext implements ContextInterface
{
    /**
     * @var object[]
     */
    private $stack = [];

    /**
     * @param string $name
     * @param array $options
     * @param bool $graceful
     * @return void
     * @throws \Exception
     */
    public function initContext($name, array $options, $graceful = false)
    {
        if (isset($this->stack[$name])) {
            if ($graceful) {
                $this->stack[$name];
            } else {
                throw new \Exception(sprintf('Context with name "%s" already exists.', $name));
            }
        } else {
            $context = $this->createContext($options);
            if ($context) {
                $this->stack[$name] = $context;
            } else {
                throw new \Exception(sprintf('Context with name "%s" cannot be created.', $name));
            }
        }
    }

    /**
     * @param string $name
     * @return object|null
     */
    public function getContext($name)
    {
        return $this->stack[$name] ?? null;
    }

    /**
     * @param array $options
     * @return object
     */
    abstract protected function createContext(array $options);
}