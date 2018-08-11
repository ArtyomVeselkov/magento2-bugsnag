<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Optimlight\Bugsnag\Model\Queue\Builder;

use Optimlight\Bugsnag\Model\Queue\ClientInterface;

/**
 * Interface BuilderInterface
 * @package Optimlight\Bugsnag\Model\Queue\Builder
 */
interface BuilderInterface
{
    /**
     * @param array $arguments
     * @return ClientInterface
     */
    public function build(array $arguments = []);
}
