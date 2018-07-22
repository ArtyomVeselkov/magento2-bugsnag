<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Optimlight\Bugsnag\Model\Queue;

/**
 * Interface ClientInterface
 * @package Optimlight\Bugsnag\Model\Queue
 */
interface ClientInterface
{
    /**
     * Header used for setting unique ID to distinguish messages each from other.
     */
    const HEADER_BUGSNAG_QUEUE_ID = 'bg_queue_id';

    /**
     * Property which shows that message requires @see json_decode function.
     */
    const PROPERTY_JSON_ENCODED = 'bg_queue_json_encoded';

    /**
     * @param mixed $value
     * @return void
     */
    public function enqueue($value);

    /**
     * @return RecordMediatorInterface
     */
    public function dequeue();

    /**
     * @return void
     */
    public function purgeQueue();
}
