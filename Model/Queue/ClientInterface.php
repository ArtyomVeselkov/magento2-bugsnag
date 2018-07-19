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