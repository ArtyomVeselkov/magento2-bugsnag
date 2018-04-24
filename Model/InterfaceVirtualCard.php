<?php

/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

namespace Optimlight\Bugsnag\Model;

use Optimlight\Bugsnag\Model\Client\AbstractClient;

/**
 * Interface InterfaceVirtualCard
 * @package Optimlight\Bugsnag
 */
interface InterfaceVirtualCard
{
    /**
     * @return bool
     */
    public function validate();
    /**
     * @param int $errorNo
     * @param string $errorStr
     * @param string $errorFile
     * @param int $errorLine
     * @return bool
     * @throws \Exception
     */
    public function execute($errorNo, $errorStr, $errorFile, $errorLine, $lastError);

    /**
     * @return AbstractClient
     */
    public function getClient();

    /**
     * @return int
     */
    public function getId();

    /**
     * @return string
     */
    public function getName();
}