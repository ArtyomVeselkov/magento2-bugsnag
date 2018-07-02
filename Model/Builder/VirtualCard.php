<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

namespace Optimlight\Bugsnag\Model\Builder;

use Optimlight\Bugsnag\Model\BuildableInterface;
use Optimlight\Bugsnag\Model\VirtualCardFactory;
use Optimlight\Bugsnag\Model\InterfaceVirtualCard;
use Optimlight\Bugsnag\Helper\SetRecursiveData;
use Magento\Framework\DataObject;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class VirtualCard
 * @package Optimlight\Bugsnag\Model
 *
 * This class is not tested, experimental.
 */
class VirtualCard implements BuilderInterface
{
    /**
     * @var array
     */
    private $arguments = [];

    /**
     * @var array
     */
    private $map = [];

    /**
     * @var VirtualCardFactory
     */
    private $cardFactory;

    /**
     * @var SetRecursiveData
     */
    private $setRecursiveData;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * VirtualCard constructor.
     * @param VirtualCardFactory $cardFactory
     * @param SetRecursiveData $setRecursiveData
     * @param ObjectManagerInterface $objectManager
     * @param array $map
     */
    public function __construct(
        SetRecursiveData $setRecursiveData,
        ObjectManagerInterface $objectManager,
        VirtualCardFactory $cardFactory = null,
        array $map = []
    ) {
        $this->cardFactory = $cardFactory;
        $this->setRecursiveData = $setRecursiveData;
        $this->objectManager = $objectManager;
        $this->map = $map;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function setArgument($name, $value)
    {
        $this->arguments[$name] = $value;
        return $this;
    }

    /**
     * @param array $map
     * @return $this
     */
    public function setMap($map)
    {
        $this->map = $map;
        return $this;
    }

    /**
     * @return BuildableInterface|InterfaceVirtualCard
     */
    public function build()
    {
        $arguments = $this->processArguments();
        return $this->cardFactory->create($arguments);
    }

    /**
     * $arguments = [
     *      'sort' => [
     *           'key1' => 1, // position
     *           'key2' => 2,
     *           'key3' => null,
     *           'key4' => 3,
     *           'key5' => null
     *      ],
     *      'move' => [
     *           'key5' => 'key3/subkey1'
     *      ],
     *      'copy' => [
     *           'key4' => 'key3/subkey2/subsubkey'
     *      ],
     *      'create' => [
     *           'key3' => '\Some\Vendor\Class' // will use array of 'key3' as $data argument
     *      ]
     * ]
     */
    private function processArguments()
    {
        $arguments = new DataObject();
        $copyRules = $this->map['copy'] ?? [];
        foreach ($copyRules as $from => $to) {
            $this->processSingleArgument('copy', $from, $to, $arguments);
        }
        $moveRules = $this->map['move'] ?? [];
        foreach ($moveRules as $from => $to) {
            $this->processSingleArgument('copy', $from, $to, $arguments);
        }
        $moveRules = $this->map['create'] ?? [];
        foreach ($moveRules as $from => $to) {
            $this->processSingleArgument('create', $from, $to, $arguments);
        }
        $sort = $this->map['sort'] ?? [];
        if (count($sort)) {
            $intersect = array_intersect_assoc($sort, $arguments->toArray());
            $different = array_diff_assoc($arguments->toArray(), $sort);
            ksort($intersect);
            $values = $arguments->toArray(array_keys($intersect));
            $values += $different;
            $arguments->setData($values);
        }
        return $arguments->toArray();
    }

    /**
     * @param string $field
     * @param string $from
     * @param string $to
     * @param DataObject $container
     */
    private function processSingleArgument($field, $from, $to, $container)
    {
        switch ($field) {
            case 'move':
                $this->setRecursiveData->setSubject($container)->setKey($to)->setValue($this->arguments[$from])->push();
                if ($container->hasData($from)) {
                    $container->unsetData($from);
                }
                break;
            case 'copy':
                $this->setRecursiveData->setSubject($container)->setKey($to)->setValue($this->arguments[$from])->push();
                break;
            case 'create':
                $type = $from;
                $data = $container->getData($to);
                $instance = $this->objectManager->create($type, ['data' => $data]);
                $this->setRecursiveData->setSubject($container)->setKey($from)->setValue($instance)->push();
                break;
        }
    }
}
