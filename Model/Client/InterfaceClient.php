<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

namespace Optimlight\Bugsnag\Model\Client;

/**
 * Interface InterfaceClient
 * @package Optimlight\Bugsnag\Client
 */
interface InterfaceClient 
{
    /**
     * @param int $errorNo
     * @param string $errorStr
     * @param string $errorFile
     * @param int $errorLine
     * @param $lastError
     * @return bool
     */
    public function execute($errorNo, $errorStr, $errorFile, $errorLine, $lastError);
}
