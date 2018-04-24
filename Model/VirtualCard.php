<?php

/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

namespace Optimlight\Bugsnag;

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
     * @param string $version
     * @param string $site
     */
    public function __construct($name, $version = '', $site = '', $id = 0, $client = null)
    {
        $this->name = $name ? is_string($name) : self::DEFAULT_NAME;
        $this->version = $version;
        $this->site = $site;
        $this->id = $id;
        $this->client = null;
    }

    /**
     * @return bool
     */
    public function validate()
    {
        return $this->client && is_a($this->client, 'Optimlight\\Bugsnag\\Client\\InterfaceClient');
    }
}

