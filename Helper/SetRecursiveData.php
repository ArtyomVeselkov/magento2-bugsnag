<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */

namespace Optimlight\Bugsnag\Helper;

use Magento\Framework\DataObject;

/**
 * Class SetRecursiveData
 */
class SetRecursiveData
{
    /**
     * @var DataObject|array
     */
    private $subject;

    /**
     * @var string
     */
    private $key;

    /**
     * @var mixed
     */
    private $value;

    /**
     * @param DataObject|array $subject
     * @return $this
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * @param string $key
     * @return $this
     */
    public function setKey($key)
    {
        $this->key = $key;
        return $this;
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $value;
    }

    /**
     * @return DataObject|array|null
     */
    public function push()
    {
        $key = $this->key;
        $object = $this->subject;
        $value = $this->value;
        return static::execute($object, $key, $value);
    }

    /**
     * @param DataObject|array $object
     * @param string $key
     * @param mixed $value
     * @return DataObject|array|null
     */
    private static function execute($object, $key, $value)
    {
        if (is_string($key) && strpos($key,'/')) {
            $key = explode('/', $key);
        }
        $k = null;

        $isObject = is_object($object) && is_a($object, DataObject::class);
        if ($isObject) {
            $data = $object->getData();
        } elseif (is_array($object)) {
            $data = $object;
        } else {
            // Key already exists, return value.
            return $value;
        }

        if (is_array($key)) {
            if (0 === count($key)) {
                return $value;
            } else {
                $k = array_shift($key);
                if (isset($data[$k])) {
                    $data = static::execute($data[$k], $key, $value);
                } elseif(is_numeric($k) && is_int($k) && isset($data[(int)$k])) {
                    $k = (int)$k;
                    $data = static::execute($data[$k], $key, $value);
                } elseif (0 === count($key)) {
                    $data = $value;
                } elseif (count($key) && $value && is_array($data)) {
                    if (is_numeric($k) && $k === (string)(int)($k)) {
                        $k = (int)$k;
                    }
                    $data[$k] = [];
                    $data[$k] = static::execute($data[$k], $key, $value);
                    return $data;
                } else {
                    return null;
                }
            }
        } else {
            return null;
        }

        if (!is_null($data) || (0 === count($key)) && is_null($value)) {
            if ($isObject) {
                !is_null($k) ? $object->setData($k, $data) : $object->setData($data);
            } else {
                !is_null($k) ? $object[$k] = $data : $object = $data;
            }
        }
        return $object;
    }
}