<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Optimlight\Bugsnag\Model\Queue;

use Magento\Framework\DataObject;
use Optimlight\Bugsnag\Model\BuildableInterface;
use Interop\Queue\{
    PsrContext,
    PsrQueue,
    PsrMessage,
    Exception as QueueException,
    InvalidDestinationException as QueueInvalidDestinationException,
    InvalidMessageException as QueueInvalidMessageException
};

/**
 * Class Client
 * @package Optimlight\Bugsnag\Model\Queue
 */
class Client implements ClientInterface, BuildableInterface
{
    /**
     * Default name of the queue.
     */
    const DEFAULT_QUEUE_NAME = 'default';

    /**
     * Default message lifetime.
     */
    const DEFAULT_MESSAGE_LIFETIME = 0;

    /**
     * @var PsrContext
     */
    private $context = null;

    /**
     * @var string
     */
    private $defaultQueue = self::DEFAULT_QUEUE_NAME;

    /**
     * @var PsrQueue[]
     */
    private $registry = [];

    /**
     * @var RecordMediatorFactoryInterface
     */
    private $factory;

    /**
     * Client constructor.
     * @param PsrContext $context
     * @param string $defaultQueue
     */
    public function __construct(PsrContext $context, $defaultQueue = self::DEFAULT_QUEUE_NAME)
    {
        $this->context = $context;
        $this->defaultQueue = $defaultQueue;
        $this->factory = new RecordMediatorFactory();
    }

    /**
     * @param mixed $value
     * @param string $queueName
     * @param int $liftTime
     * @throws QueueException
     * @throws QueueInvalidDestinationException
     * @throws QueueInvalidMessageException
     */
    public function enqueue($value, $queueName = self::DEFAULT_QUEUE_NAME, $liftTime = self::DEFAULT_MESSAGE_LIFETIME)
    {
        $queue = $this->getQueue($queueName);
        if ($this->hasContext() && $queue) {
            $message = $this->createMessage($value);
            if ($message) {
                $producer = $this->context->createProducer();
                if ($producer && $liftTime) {
                    $producer->setTimeToLive($liftTime);
                }
                $producer->send($queue, $message);
            }
        }
    }

    /**
     * @param string $queueName
     * @return RecordMediatorInterface|null
     */
    public function dequeue($queueName = self::DEFAULT_QUEUE_NAME)
    {
        $result = null;
        $queue = $this->getQueue($queueName);
        if ($this->hasContext() && $queue) {
            $consumer = $this->context->createConsumer($queue);
            if ($consumer) {
                $message = $consumer->receive();
                $result = $this->factory->create($message, $consumer);
            }
        }
        return $result;
    }

    /**
     * @param string $queueName
     */
    public function purgeQueue($queueName = self::DEFAULT_QUEUE_NAME)
    {
        $queue = $this->getQueue($queueName);
        if ($this->hasContext() && $queue) {
            if (method_exists($this->context, 'purge')) {
                $this->context->purge($queue);
            }
        }
    }

    /**
     * @return bool
     */
    public function hasContext()
    {
        return is_a($this->context, PsrContext::class);
    }

    /**
     * @param string $queueName
     * @return PsrQueue|null
     */
    public function getQueue($queueName = self::DEFAULT_QUEUE_NAME)
    {
        if (!isset($this->registry[$queueName])) {
            $this->registry[$queueName] = $this->hasContext() ? $this->context->createQueue($queueName) : null;
        }
        return $this->registry[$queueName];
    }

    /**
     * @inheritdoc
     * @throws \Exception
     */
    public static function getInstance(...$arguments)
    {
        return new static(...$arguments);
    }

    /**
     * @param mixed $value
     * @return PsrMessage|null
     */
    private function createMessage($value)
    {
        $message = null;
        if (!$this->hasContext()) {
            return $message;
        }
        if (is_a($value, DataObject::class)) {
            /** @var DataObject $value */
            $value = $value->getData();
        }
        if (is_array($value) && isset($value['properties']) && isset($value['body']) && isset($value['headers'])) {
            $body = $value['body'];
            $properties = is_array($value['properties']) ? $value['properties'] : [$value['properties']];
            $headers = is_array($value['headers']) ? $value['headers'] : $value['headers'];
            $message = $this->context->createMessage($body, $properties, $headers);
        } elseif (!is_string($value)) {
            $value = \json_encode($value);
            $message = $this->context->createMessage($value);
        } else {
            $message = $this->context->createMessage($value);
        }
        return $message;
    }
}