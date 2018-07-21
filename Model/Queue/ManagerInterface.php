<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

namespace Optimlight\Bugsnag\Model\Queue;

/**
 * Interface ManagerInterface
 * @package Optimlight\Bugsnag\Model\Queue
 */
interface ManagerInterface
{
    /**
     * @param array $cardName
     * @param array $options
     */
    public function dequeue($cardName = [], array $options = []);
}