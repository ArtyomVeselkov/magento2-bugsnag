<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Optimlight\Bugsnag\Model\Queue;

/**
 * Interface RecordMediatorInterface
 * @package Optimlight\Bugsnag\Model\Queue
 */
interface RecordMediatorInterface
{
    const STATUS_PENDING = 0;
    const STATUS_FINISHED = 1;
    const STATUS_REJECTED = 2;

    /**
     *
     */
    public function acknowledge();

    /**
     * @param bool $requeue
     */
    public function reject($requeue = false);

    /**
     * @return string
     */
    public function getMessage();

    /**
     * @return object
     */
    public function getMessageRaw();
}