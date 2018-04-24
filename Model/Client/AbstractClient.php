<?php

/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

namespace Optimlight\Bugsnag\Model\Client;

/**
 * Class AbstractClient
 * @package Optimlight\Bugsnag\Client
 */
abstract class AbstractClient implements InterfaceClient
{
    /**
     * @var array 
     */
    protected $rawConfig = [];

    /**
     * AbstractClient constructor.
     * @param array $configuration
     */
    public function __construct($configuration = [])
    {
        $this->rawConfig = $configuration;
    }

    /**
     * @param int $errorNo
     * @param string $errorStr
     * @param string $errorFile
     * @param int $errorLine
     * @return bool
     * @throws \Exception
     */
    abstract public function execute($errorNo, $errorStr, $errorFile, $errorLine, $lastError);
}