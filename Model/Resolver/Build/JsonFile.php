<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

namespace Optimlight\Bugsnag\Model\Resolver\Build;

use Magento\Framework\DataObject;
use Magento\Framework\Filesystem\DriverPool;
use Magento\Framework\Filesystem\File\Read as File;
use Magento\Framework\Filesystem\File\ReadFactory as FileFactory;
use Zend\Json\Json;

/**
 * Class JsonFile
 * @package Optimlight\Bugsnag\Model\Resolver\Build
 */
class JsonFile extends BuildAbstract
{
    /**
     * @var FileFactory
     */
    private $readFactory;

    /**
     * JsonFile constructor.
     * @param FileFactory $readFactory
     * @param Json $json
     * @param array $data
     */
    public function __construct(
        FileFactory $readFactory,
        array $data = []
    ) {
        parent::__construct($data);
        $this->json = $json;
        $this->readFactory = $readFactory;
    }

    /**
     * @param string $destination
     * @param string $nestedPath
     * @return bool|array
     */
    public function resolveData($destination, $nestedPath)
    {
        // Result.
        $result = false;
        // Get type of external resource.
        $type = $this->data->getData(static::DESTINATION_TYPE) ?? DriverPool::FILE;
        // Prepare path for accessing resource.
        if (DriverPool::FILE === $type) {
            $bp = BP;
            $root = is_link($bp) ? readlink($bp) : $bp;
            if (!strlen($destination)) {
                return $result;
            } elseif (is_string($destination) && !in_array($destination[0], ['\\', '/'])) {
                $destination = $root . DIRECTORY_SEPARATOR . $destination;
            }
            $path = realpath($destination);
            if (!file_exists($path)) {
                return $result;
            }
        } else {
            $path = $destination;
        }
        $content = null;
        // Try get data.
        if ($path) {
            /** @var File $read */
            $read = $this->readFactory->create($path, $type);
            $content = $read->readAll();
        }
        // In case of successful reading - decode data.
        if ($content) {
            $json = Json::decode($content, Json::TYPE_ARRAY);
            $buffer = new DataObject($json);
            $result = $buffer->getData($this->data->getData(static::NESTED_PATH_INFO_KEY));
        }
        return $result;
    }

    /**
     * @param mixed ...$arguments
     * @return JsonFile
     */
    public static function getInstance(...$arguments)
    {
        $arguments[] = new FileFactory(new DriverPool());
        rsort($arguments);
        return new static(...$arguments);
    }
}
