<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Optimlight\Bugsnag\Model\Queue\Builder;

use Optimlight\Bugsnag\Model\Queue\{Client as QueueClient, ClientInterface, Builder\ClientInterface as BuilderInterface};
use Optimlight\Bugsnag\Model\Queue\Provider\ContextInterface as ContextProviderInterface;

/**
 * Class Client
 * @package Optimlight\Bugsnag\Model\Queue\Builder
 */
class Client implements BuilderInterface
{
    const FLOW_ARGUMENTS = 'arg';
    const FLOW_RESOLVED = 'resolved';
    const FLOW_RESOLVED_CONTEXT_NAME = 'context_name';
    const FLOW_RESOLVED_PROVIDER_CLASS = 'provider_class';
    const FLOW_RESOLVED_PROVIDER_OPTIONS = 'provider_options';
    const FLOW_RESOLVED_PROVIDER = 'provider';
    const FLOW_RESOLVED_CONTEXT = 'context';
    const FLOW_RESOLVED_CLIENT = 'client';

    /**
     * @param array $arguments
     * @return null|ClientInterface
     * @throws \Exception
     */
    public function build(array $arguments = [])
    {
        $flow = $this->getFlow($arguments);
        $this->resolveArguments($flow);
        $this->resolveInstances($flow);
        return $this->getResolvedValue($flow, self::FLOW_RESOLVED_CLIENT);
    }

    /**
     * @param array $arguments
     * @return array
     */
    private function getFlow($arguments)
    {
        $arguments = isset($arguments['queue']) && is_array($arguments['queue']) ? $arguments['queue'] : $arguments;
        return [
            self::FLOW_ARGUMENTS => $arguments,
            self::FLOW_RESOLVED => [
                self::FLOW_RESOLVED_PROVIDER_CLASS => null,
                self::FLOW_RESOLVED_PROVIDER_OPTIONS => []
            ]
        ];
    }

    /**
     * @param $flow
     */
    private function resolveArguments(&$flow)
    {
        $options = $this->getConfigValue($flow, 'provider_options');
        $this->setResolvedValue(
            $flow,
            self::FLOW_RESOLVED_PROVIDER_OPTIONS,
            is_array($options) ? $options : []
        );

        $class = $this->getConfigValue($flow, 'provider_class');
        $this->setResolvedValue(
            $flow,
            self::FLOW_RESOLVED_PROVIDER_CLASS,
            \class_exists($class, true) ? $class : null
        );

        $name = $this->getConfigValue($flow, 'context_name');
        $this->setResolvedValue(
            $flow,
            self::FLOW_RESOLVED_CONTEXT_NAME,
            is_string($name) && strlen($name) ? $name : 'bugsnag_queue'
        );
    }

    /**
     * @param $flow
     * @throws \Exception
     */
    private function resolveInstances(&$flow)
    {
        // 1. Resolve context provider.
        $class = $this->getResolvedValue($flow, self::FLOW_RESOLVED_PROVIDER_CLASS);
        if ($class) {
            /** @var ContextProviderInterface $instance */
            $instance = new $class();
            // We check class after instantiating as it can be checked incorrectly otherwise.
            if (!is_a($instance, ContextProviderInterface::class)) {
                throw new \InvalidArgumentException(sprintf(
                        'Wrong class "%s" supplied as Context Provider. Class must implement interface "%s".',
                        $class,
                        ContextProviderInterface::class
                    ));
            }
        } else {
            throw new \InvalidArgumentException(
                sprintf('Wrong class "%s" supplied as Context Provider.', $class)
            );
        }
        // 2. Create context.
        $options = $this->getResolvedValue($flow, self::FLOW_RESOLVED_PROVIDER_OPTIONS);
        $name = $this->getResolvedValue($flow, self::FLOW_RESOLVED_CONTEXT_NAME);
        $instance->initContext($name, $options);
        if (!$context = $instance->getContext($name)) {
            throw new \Exception(sprintf('Context "%s" cannot be created or retried.', $name));
        }
        $this->setResolvedValue($flow, self::FLOW_RESOLVED_CONTEXT, $context);
        // 3. Create client.
        $client = QueueClient::getInstance($context);
        $this->setResolvedValue($flow, self::FLOW_RESOLVED_CLIENT, $client);
    }

    /**
     * @param array $arguments
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    private function getConfigValue(array $arguments, $key, $default = null)
    {
        return isset($arguments[self::FLOW_ARGUMENTS][$key]) ? $arguments[self::FLOW_ARGUMENTS][$key] : $default;
    }

    /**
     * @param array $flow
     * @param string $key
     * @param mixed $value
     */
    private function setResolvedValue(array &$flow, $key, $value)
    {
        $flow[self::FLOW_RESOLVED][$key] = $value;
    }

    /**
     * @param array $flow
     * @param string $key
     * @return mixed
     */
    private function getResolvedValue(array $flow, $key)
    {
        return isset($flow[self::FLOW_RESOLVED][$key]) ? $flow[self::FLOW_RESOLVED][$key] : null;
    }
}
