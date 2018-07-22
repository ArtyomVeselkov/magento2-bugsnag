<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Optimlight\Bugsnag\Model\Client;

use Optimlight\Bugsnag\Logger\Php as Logger;

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
     * @var Logger
     */
    protected $phpLogger;

    /**
     * AbstractClient constructor.
     * @param array $configuration
     */
    public function __construct($configuration = [])
    {
        $this->rawConfig = $configuration;
        $this->phpLogger = new Logger();
    }

    /**
     * @param mixed ...$arguments
     * @return AbstractClient
     */
    public static function getInstance(...$arguments)
    {
        return new static(...$arguments);
    }

    /**
     * @param int $errorNo
     * @param string $errorStr
     * @param string $errorFile
     * @param int $errorLine
     * @param $lastError
     * @return bool
     */
    abstract public function execute($errorNo, $errorStr, $errorFile, $errorLine, $lastError);
}
