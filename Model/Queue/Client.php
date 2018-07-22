<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Optimlight\Bugsnag\Model\Queue;

use Magento\Framework\DataObject;
use Optimlight\Bugsnag\Model\BuildableInterface;
use Optimlight\Bugsnag\Logger\Php as Logger;
use Interop\Queue\{
    PsrConsumer, PsrContext, PsrQueue, PsrMessage, Exception as QueueException, InvalidDestinationException as QueueInvalidDestinationException, InvalidMessageException as QueueInvalidMessageException
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
     * @var PsrConsumer[]
     */
    private $consumers = [];

    /**
     * @var int
     */
    private static $increment = 0;

    /**
     * @var array
     */
    private $lastReceived = [];

    /**
     * Logger (not Monolog).
     *
     * @var Logger
     */
    private $phpLogger;

    /**
     * Client constructor.
     * @param PsrContext $context
     * @param Logger $logger
     * @param string $defaultQueue
     */
    public function __construct(PsrContext $context, Logger $logger, $defaultQueue = self::DEFAULT_QUEUE_NAME)
    {
        $this->context = $context;
        $this->defaultQueue = $defaultQueue;
        $this->factory = new RecordMediatorFactory();
        $this->phpLogger = $logger;
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
            if (is_a($message, PsrMessage::class)) {
                $this->beforeEnqueueMessage($message);
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
            if ($consumer = $this->getConsumer($queue, $queueName)) {
                if ($message = $this->getNextMessage($consumer)) {
                    $this->afterDequeueMessage($message);
                    $result = $this->factory->create($message, $consumer);
                }
            }
        }
        return $result;
    }

    /**
     * @param PsrConsumer $consumer
     * @return PsrMessage|null
     */
    private function getNextMessage(PsrConsumer $consumer)
    {
        // (1) There is a place of possible problem. FsConsumer uses blocks of size = 64 to gain a whole frame.
        //      Also it utilizes a recursive approach. As single message can be much more bigger and there is a limit
        //      for max nesting level in PHP, receiving next message can lead to a fatal error.
        // (2) We define explicitly timeout. In case of being se to 0 it would wait & check for pulling for unlimited
        //      period of time.
        $message = $consumer->receive(1);
        if ($message) {
            $hash = spl_object_hash($consumer);
            $lastReceived = isset($this->lastReceived[$hash]) ? $this->lastReceived[$hash] : false;
            $header = $message->getHeader(self::HEADER_BUGSNAG_QUEUE_ID);
            if (!$lastReceived || ($lastReceived !== $header) || !$header) {
                $this->lastReceived[$hash] = $header;
                return $message;
            } else {
                return null;
            }
        }
        return null;
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
        $arguments[1] = $arguments[1] ?? new Logger();
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
            $message->setProperty(self::PROPERTY_JSON_ENCODED, true);
        } else {
            $message = $this->context->createMessage($value);
        }

        return $message;
    }

    /**
     * @return int
     */
    private function getIncrement()
    {
        return ++static::$increment;
    }

    /**
     * @return string
     */
    private function getUniqueHash()
    {
        return uniqid('bgm-' . $this->getIncrement());
    }

    /**
     * @param PsrMessage $message
     * @return void
     */
    private function beforeEnqueueMessage(PsrMessage $message)
    {
        $hash = $this->getUniqueHash();
        $message->setHeader(self::HEADER_BUGSNAG_QUEUE_ID, $hash);
    }

    /**
     * @param PsrMessage $message
     * @return void
     */
    private function afterDequeueMessage(PsrMessage $message)
    {
        if ($message->getProperty(self::PROPERTY_JSON_ENCODED)) {
            try {
                $body = $message->getBody();
                $body = \json_decode($body, true);
                // Method expects string, but we set array.
                $message->setBody($body);
            } catch (\Exception $exception) {
                $this->phpLogger->catchException($exception);
            }
        }
    }

    /**
     * @param PsrQueue $queue
     * @param string $queueName
     * @return PsrConsumer
     */
    private function getConsumer(PsrQueue $queue, $queueName = self::DEFAULT_QUEUE_NAME)
    {
        if (!isset($this->consumers[$queueName])) {
            $consumer = $this->context->createConsumer($queue);
            if (method_exists($consumer, 'setPreFetchCount')) {
                $consumer->setPreFetchCount(100);
            }
            $this->consumers[$queueName] = $consumer;
        }
        return $this->consumers[$queueName];
    }
}
