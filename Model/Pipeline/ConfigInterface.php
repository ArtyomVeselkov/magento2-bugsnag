<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Optimlight\Bugsnag\Model\Pipeline;

/**
 * Interface ConfigInterface
 * @package Optimlight\Bugsnag\Model\Pipeline
 */
interface ConfigInterface
{
    /**
     * @param string $key
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function getConfig($key, $default = null);
}
