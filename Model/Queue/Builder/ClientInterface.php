<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Optimlight\Bugsnag\Model\Queue\Builder;

use Optimlight\Bugsnag\Model\Queue\ClientInterface as QueueClientInterface;

/**
 * Interface ClientInterface
 * @package Optimlight\Bugsnag\Model\Queue\Builder
 */
interface ClientInterface extends BuilderInterface
{
    /**
     * @param array $arguments
     * @return QueueClientInterface
     */
    public function build(array $arguments = []);
}