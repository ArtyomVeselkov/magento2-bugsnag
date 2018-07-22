<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Optimlight\Bugsnag\Model\Queue;

use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrMessage;

interface RecordMediatorFactoryInterface
{
    /**
     * @param PsrMessage $message
     * @param PsrConsumer $consumer
     * @param int $status
     * @return RecordMediatorInterface
     */
    public function create(
        PsrMessage $message,
        PsrConsumer $consumer,
        $status = RecordMediatorInterface::STATUS_PENDING
    );
}
