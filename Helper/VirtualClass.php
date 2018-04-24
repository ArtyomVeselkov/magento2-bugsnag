<?php

/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

namespace Optimlight\Bugsnag\Helper;

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
     * @var Data
     */
    protected $dataHelper;
    /**
     * @var ConfigInterface
     */
    protected $config;
    /**
     * @var array|null
     */
    protected static $virtualTypes = null;
    /**
     * @var array
     */
    protected static $filteredTypes = [];

    /**
     * VirtualClass constructor.
     * @param Data $dataHelper
     * @param ConfigInterface $config
     * @param Context $context
     */
    public function __construct(
        Common $dataHelper,
        ConfigInterface $config,
        Context $context

    )
    {
        parent::__construct($context);
        $this->dataHelper = $dataHelper;
        $this->config = $config;
    }

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
                $result[$virtualTypeName] = null;
            }
        }, ARRAY_FILTER_USE_BOTH);
        return $result;
    }

    /**
     * @param 
     * @param array $data
     * @return mixed
     */
    public function initVirtualTypes(&$result, $data = [])
    {
        $objectManager = Common::getObjectManager();
        foreach ($result as $virtualTypeName => &$virtualTypeObject) {
            try {
                $buffer = $objectManager->get($virtualTypeName, ['data' => $data]);
                if (is_a($virtualTypeObject, 'Optimlight\\Bugsnag\\InterfaceVirtualCard'))
                $virtualTypeObject = $buffer;
            } catch (\Exception $exception) {
                $virtualTypeObject = null;
            }
        }
        return $result;
    }
}