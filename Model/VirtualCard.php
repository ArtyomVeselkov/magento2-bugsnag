<?php

/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

namespace Optimlight\Bugsnag\Model;

/**
 * Class VirtualCard
 * @package Optimlight\Bugsnag
 */
class VirtualCard implements InterfaceVirtualCard
{
    const DEFAULT_NAME = 'Bugsnag M2 Integration';

    /**
     * @var string
     */
    public $name = '';
    /**
     * @var string
     */
    public $site = '';
    /**
     * @var string
     */
    public $version = '';
    /**
     * @var string[]
     */
    public $extra = [];
    /**
     * @var int
     */
    public $id = 0;
    /**
     * @var null|\Optimlight\Bugsnag\Client\AbstractClient
     */
    public $client = null;
    /**
     * @var bool
     */
    public $active = true;

    /**
     * VirtualCard constructor.
     *
     * @param string $name
     * @param int $id
     * @param null|\Optimlight\Bugsnag\Model\Client\AbstractClient $client
     * @param string $version
     * @param string $site
     * @param bool $active
     */
    public function __construct($name, $id = 0, $client = null, $version = '', $site = '', $active = true)
    {
        $this->name = is_string($name) ? $name : self::DEFAULT_NAME;
        $this->version = $version;
        $this->site = $site;
        $this->id = $id + 1;
        $this->client = $client;
        $this->active = $active;
    }

    /**
     * @return bool
     */
    public function validate()
    {
        $client = $this->getClient();
        return $client && is_a($client, 'Optimlight\\Bugsnag\\Model\\Client\\InterfaceClient');
    }

    /**
     * @param int $errorNo
     * @param string $errorStr
     * @param string $errorFile
     * @param int $errorLine
     * @param $lastError
     * @return bool|void
     * @throws \Exception
     */
    public function execute($errorNo, $errorStr, $errorFile, $errorLine, $lastError)
    {
        if (!$this->active) {
            return ;
        }
        $this->client->execute($errorNo, $errorStr, $errorFile, $errorLine, $lastError);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return null|\Optimlight\Bugsnag\Client\AbstractClient|Client\AbstractClient
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}

