<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

namespace Optimlight\Bugsnag\Model\Client;

/**
 * Class BugsnagJs
 * @package Optimlight\Bugsnag\Client
 *
 * Do nothing in this class as for tracking errors is using JS which is included via Block.
 */
class BugsnagJs extends AbstractClient
{
    /**
     * Main function for tracking exceptions.
     *
     * @param $errorNo
     * @param $errorStr
     * @param $errorFile
     * @param $errorLine
     * @param $lastError
     * @return bool
     */
    public function execute($errorNo, $errorStr, $errorFile, $errorLine, $lastError)
    {
        return false;
    }
}
