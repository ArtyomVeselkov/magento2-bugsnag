<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Optimlight\Bugsnag\Model\Queue;

/**
 * Interface ProcessorCallbackInterface
 * @package Optimlight\Bugsnag\Model\Queue
 */
interface ProcessorCallbackInterface
{
    /**
     * @param array $arguments
     * @return mixed
     */
    public function execute(array $arguments = []);
}