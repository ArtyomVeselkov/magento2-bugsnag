<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Optimlight\Bugsnag\Model\Pipeline;

/**
 * Class ProcessorDefault
 * @package Optimlight\Bugsnag\Model\Pipeline
 */
class ProcessorDefault implements ProcessorInterface
{
    /**
     * @param PayloadInterface $payload
     * @param StageInterface[] ...$stages
     *
     * @return PayloadInterface
     */
    public function process(PayloadInterface $payload, ...$stages)
    {

    }
}
