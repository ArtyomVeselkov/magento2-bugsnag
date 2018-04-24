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
     * VirtualCard constructor.
     * @param string $name
     * @param int $id
     * @param null|\Optimlight\Bugsnag\Model\Client\AbstractClient $client
     * @param string $version
     * @param string $site
     */
    public function __construct($name, $id = 0, $client = null, $version = '', $site = '')
    {
        $this->name = $name ? is_string($name) : self::DEFAULT_NAME;
        $this->version = $version;
        $this->site = $site;
        $this->id = $id + 1;
        $this->client = $client;
    }

    /**
     * @return bool
     */
    public function validate()
    {
        $clinet = $this->getClient();
        return $clinet && is_a($clinet, 'Optimlight\\Bugsnag\\Client\\InterfaceClient');
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

