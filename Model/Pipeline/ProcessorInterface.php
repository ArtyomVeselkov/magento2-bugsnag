<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Optimlight\Bugsnag\Model\Pipeline;

/**
 * Interface ProcessorInterface
 * @package Optimlight\Bugsnag\Model\Pipeline
 */
interface ProcessorInterface
{
    /**
     * @param PayloadInterface $payload
     * @param StageInterface[] ...$stages
     *
     * @return PayloadInterface
     */
    public function process(PayloadInterface $payload, ...$stages);
}
