<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Optimlight\Bugsnag\Model\Pipeline;

/**
 * Interface MatcherInterface
 * @package Optimlight\Bugsnag\Model\Pipeline
 */
interface MatcherInterface
{
    /**
     * @param PayloadInterface $payload
     *
     * @return bool
     */
    public function execute(PayloadInterface $payload);
}
