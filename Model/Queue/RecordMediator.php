<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Optimlight\Bugsnag\Model\Queue;

use Optimlight\Bugsnag\Model\BuildableInterface;
use Interop\Queue\{PsrConsumer, PsrMessage};

/**
 * Class RecordMediator
 * @package Optimlight\Bugsnag\Model\Queue
 */
class RecordMediator implements RecordMediatorInterface, BuildableInterface
{
    /**
     * @var PsrMessage
     */
    private $message;

    /**
     * @var PsrConsumer
     */
    private $consumer;

    /**
     * @var int
     */
    private $status = self::STATUS_PENDING;

    /**
     * RecordMediator constructor.
     * @param PsrMessage $message
     * @param PsrConsumer $consumer
     * @param int $status
     */
    public function __construct(
        PsrMessage $message,
        PsrConsumer $consumer,
        $status = self::STATUS_PENDING
    ) {
        $this->message = $message;
        $this->consumer = $consumer;
        $this->status = $status;
    }

    /**
     *
     */
    public function acknowledge()
    {
        $this->consumer->acknowledge($this->message);
    }

    /**
     * @param bool $requeue
     */
    public function reject($requeue = false)
    {
        $this->consumer->reject($this->message, $requeue);
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message->getBody();
    }

    /**
     * @return PsrMessage
     */
    public function getMessageRaw()
    {
        return $this->message;
    }

    /**
     * @param mixed ...$arguments
     * @return RecordMediatorInterface
     */
    public static function getInstance(...$arguments)
    {
        return new static(...$arguments);
    }
}