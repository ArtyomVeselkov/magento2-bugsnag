<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

namespace Optimlight\Bugsnag\Model\Queue;

/**
 * Interface ProcessorInterface
 * @package Optimlight\Bugsnag\Model\Queue
 */
interface ProcessorInterface
{
    const OPTION_LIMIT = 'limit';

    const OPTION_LIMIT_DEFAULT = 300;

    /**
     * @param ClientInterface $client
     * @param array $options
     */
    public function dequeue($client, $options);
}