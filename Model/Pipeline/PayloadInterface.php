<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Optimlight\Bugsnag\Model\Pipeline;

/**
 * Interface PayloadInterface
 * @package Optimlight\Bugsnag\Model\Pipeline
 */
interface PayloadInterface
{
    /**
     * @param $result
     *
     * @return void
     */
    public function setResult($result);

    /**
     * @return mixed
     */
    public function getResult();
}
