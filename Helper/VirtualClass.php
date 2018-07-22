<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Optimlight\Bugsnag\Helper;

use Optimlight\Bugsnag\Model\InterfaceVirtualCard;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\ObjectManager\ConfigInterface;

/**
 * Class VirtualClass
 * @package Optimlight\Bugsnag\Helper
 */
class VirtualClass extends AbstractHelper
{
    /**
     * Extension's Helper class.
     *
     * @var Common
     */
    private $dataHelper;

    /**
     * Config class.
     *
     * @var ConfigInterface
     */
    private $config;

    /**
     * Collected all virtual types from di.xml.
     *
     * @var array|null
     */
    private static $virtualTypes = null;

    /**
     * Collected cards from di.xml
     *
     * @var array
     */
    private static $filteredTypes = [];

    /**
     * VirtualClass constructor.
     * @param Common $dataHelper
     * @param ConfigInterface $config
     * @param Context $context
     */
    public function __construct(
        Common $dataHelper,
        ConfigInterface $config,
        Context $context

    ) {
        parent::__construct($context);
        $this->dataHelper = $dataHelper;
        $this->config = $config;
    }

    /**
     * @return array|null
     */
    public function getVirtualTypes()
    {
        if (!is_array(static::$virtualTypes)) {
            static::$virtualTypes = $this->config->getVirtualTypes();
        }
        return static::$virtualTypes;
    }

    /**
     * @param $prefix
     * @return object[]
     */
    public function filterVirtualTypes($prefix)
    {
        $result = [];
        $virtualTypes = $this->getVirtualTypes();
        array_filter($virtualTypes, function($virtualTypeClass, $virtualTypeName) use (&$result, $prefix) {
            if (0 === strpos($virtualTypeName, $prefix)) {
                $result[$virtualTypeName] = $virtualTypeClass;
            }
        }, ARRAY_FILTER_USE_BOTH);
        return $result;
    }

    /**
     * @param $result
     * @param array $data
     * @return mixed
     */
    public function initVirtualTypes(&$result, $data = [])
    {
        $objectManager = Common::getObjectManager();
        foreach ($result as $virtualTypeName => &$virtualTypeObject) {
            try {
                // We do not use @see Optimlight\Bugsnag\Model\InterfaceVirtualCardFactory here.
                /** @var InterfaceVirtualCard $buffer */
                $buffer = $objectManager->create($virtualTypeName, ['data' => $data]);
                if (is_array($buffer->getClient()) && is_array($buffer->getConfig())) {
                    $clientArray = $buffer->getClient();
                    // TODO Add additional validation.
                    if (isset($clientArray['instance']) /*&& is_a($clientArray['instance'], \Optimlight\Bugsnag\Model\Client\InterfaceClient::class)*/) {
                        $client = $objectManager->create($clientArray['instance'], ['configuration' => $buffer->getConfig()]);
                        $buffer->setClient($client);
                    }
                }
                if (is_a($buffer, InterfaceVirtualCard::class)) {
                    $virtualTypeObject = $buffer;
                }
            } catch (\Exception $exception) {
                $virtualTypeObject = null;
            }
        }
        return $result;
    }
}
