<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Optimlight\Bugsnag\Model\Pipeline;

/**
 * Interface StageInterface
 * @package Optimlight\Bugsnag\Model\Pipeline
 */
interface StageInterface
{
    /**
     * @param PayloadInterface $payload
     *
     * @return PayloadInterface
     */
    public function __invoke(PayloadInterface $payload);
}
