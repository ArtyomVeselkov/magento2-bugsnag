<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

namespace Optimlight\Bugsnag\Model\Resolver\Build;

use Magento\Framework\DataObject;

/**
 * Class BuildAbstract
 * @package Optimlight\Bugsnag\Model\Resolver\Build
 */
abstract class BuildAbstract implements BuildInterface
{
    /**
     * @var DataObject
     */
    protected $data;

    /**
     * @var DataObject
     */
    protected $build;

    /**
     * BuildAbstract constructor.
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->data = new DataObject($data);
    }

    /**
     * @return array
     */
    public function getBuildInfo()
    {
        return $this->build->toArray() ?? [];
    }

    /**
     * @return string
     */
    public function getBuildNumber()
    {
        $value = $this->build[$this->data->getData(static::NESTED_PATH_VERSION_KEY)] ?? '';
        return is_string($value) ? $value : '';
    }

    /**
     * @param array $build
     * @return $this
     */
    public function setBuildInfo(array $build)
    {
        $this->build = $build;
        return $this;
    }

    /**
     * @return $this
     */
    public function resolve()
    {
        $build = $this->resolveData(
            $this->data->getData(static::DESTINATION_PATH_KEY),
            $this->data->getData(static::NESTED_PATH_INFO_KEY)
        );
        if (is_array($build)) {
            $this->setBuildInfo($build);
        }
        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getBuildNumber();
    }

    /**
     * @param mixed ...$arguments
     * @return BuildAbstract
     */
    public static function getInstance(...$arguments)
    {
        return new static(...$arguments);
    }

    /**
     * Function to implement retrieving of data from external resource.
     *
     * @param string $destination
     * @param string $nestedPath
     * @return bool|array
     */
    abstract public function resolveData($destination, $nestedPath);
}
