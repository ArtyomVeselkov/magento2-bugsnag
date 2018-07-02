<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

namespace Optimlight\Bugsnag\Model\Client;

use Optimlight\Bugsnag\Model\BuildableInterface;

/**
 * Interface InterfaceClient
 * @package Optimlight\Bugsnag\Client
 */
interface InterfaceClient extends BuildableInterface
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

    /**
     * @return void
     */
    public function shutdown();
}
