<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Optimlight\Bugsnag\Model\Deploy;

/**
 * Interface DeployInterface
 * @package Optimlight\Bugsnag\Model\Deploy
 */
interface DeployInterface
{
    /**
     * @return bool
     */
    public function deploy();
}
