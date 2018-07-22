<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Optimlight\Bugsnag\Model;

use Optimlight\Bugsnag\Model\Client\InterfaceClient;
use Optimlight\Bugsnag\Model\Resolver\Build\BuildInterface;

/**
 * Class VirtualCard
 * @package Optimlight\Bugsnag
 */
class VirtualCard implements InterfaceVirtualCard
{
    /**
     * Default name for all cards.
     */
    const DEFAULT_NAME = 'Bugsnag Magento 2.x Integration';

    /**
     * Name of the card.
     *
     * @var string
     */
    public $name = '';

    /**
     * Site to which belongs card's configuration.
     *
     * @var string
     */
    public $site = '';

    /**
     * Version of the card's configuration.
     *
     * @var string
     */
    public $version = '';

    /**
     * ID of the card. Must be unique.
     *
     * @var int
     */
    public $id = 0;

    /**
     * Class used for handling exceptions and errors.
     *
     * @var InterfaceClient
     */
    public $client = null;

    /**
     * Is card active.
     *
     * @var bool
     */
    public $active = true;

    /**
     * Type of the card.
     *
     * @var string
     */
    public $type = self::TYPE_PHP;

    /**
     * Configuration passed to the $client instance.
     *
     * @var array
     */
    public $config = [];

    /**
     * Secondary API key. Is optional value.
     * Example of usage case -- JS card to provide ID for URL of JS script.
     *
     * @var string
     */
    public $secondary = '';

    /**
     * API key for the target service.
     *
     * @var string
     */
    public $apikey = '';

    /**
     * @var BuildInterface|null
     */
    public $build = null;

    /**
     * VirtualCard constructor.
     * @param $name
     * @param int $id
     * @param InterfaceClient|null $client
     * @param BuildInterface|null $build
     * @param string $type
     * @param bool $active
     * @param string $apikey
     * @param string $secondary
     * @param string $version
     * @param string $site
     * @param array $config
     */
    public function __construct(
        $name,
        $id = 0,
        InterfaceClient $client = null,
        BuildInterface $build = null,
        $type = self::TYPE_PHP,
        $active = true,
        $apikey = '',
        array $config = [],
        $secondary = '',
        $version = '',
        $site = ''
    ) {
        $this->name = is_string($name) ? $name : self::DEFAULT_NAME;
        $this->version = $version;
        $this->site = $site;
        $this->id = $id;
        $this->client = $client;
        $this->active = $active;
        $this->type = $type;
        $this->secondary = $secondary;
        $this->apikey = $apikey;
        $this->build = $build;
        $this->config = array_merge(
            [
                'apikey' => $this->apikey, 'active' => $this->active, 'build_revision' => (string)$build
            ],
            $config
        );
    }

    /**
     * @return bool
     */
    public function validate()
    {
        $client = $this->getClient();
        return $client && is_a($client, InterfaceClient::class) && $this->active;
    }

    /**
     * @param int $errorNo
     * @param string $errorStr
     * @param string $errorFile
     * @param int $errorLine
     * @param $lastError
     * @return void
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
     * @return InterfaceClient
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

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return string
     */
    public function getSecondary()
    {
        return $this->secondary;
    }

    /**
     * @return string
     */
    public function getApikey()
    {
        return $this->apikey;
    }

    /**
     * @param InterfaceClient $client
     * @return $this
     */
    public function setClient(InterfaceClient $client)
    {
        $this->client = $client;
        return $this;
    }

    /**
     * @return null|BuildInterface
     */
    public function getBuild()
    {
        return $this->build;
    }

    /**
     *
     */
    public function shutdown()
    {
        if ($this->client) {
            $this->client->shutdown();
        }
    }

    /**
     * @param mixed ...$arguments
     * @return InterfaceVirtualCard
     */
    public static function getInstance(...$arguments)
    {
        return new static(...$arguments);
    }
}
