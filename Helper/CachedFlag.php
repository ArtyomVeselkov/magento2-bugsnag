<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Optimlight\Bugsnag\Helper;

/**
 * Class CachedFlag
 * @package Optimlight\Bugsnag\Helper
 */
class CachedFlag
{
    /**
     * @return bool|string
     */
    private function getPath()
    {
        $path = BP . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'bugsnag-flags.json';
        if (!\file_exists($path)) {
            return \touch($path) ? $path : false;
        } else {
            return $path;
        }
    }

    /**
     * @param string $flag
     * @param bool|mixed $default
     * @return bool|mixed
     */
    public function getFlag($flag, $default = false)
    {
        $result = $default;
        if ($path = $this->getPath()) {
            $content = \file_get_contents($path);
            if ($content) {
                try {
                    $content = \json_decode($content, true);
                    $result = $content[$flag] ?? $default;
                } catch (\Exception $exception) {
                    // We don't need to perform any actions here.
                }
            }
        }
        return $result;
    }

    /**
     * @param string $flag
     * @param bool|mixed $value
     */
    public function setFlag($flag, $value = true)
    {
        if ($path = $this->getPath()) {
            $content = \file_get_contents($path);
            try {
                $content = $content ? \json_decode($content, true) : [];
            } catch (\Exception $exception) {
                // We don't need to perform any actions here.
            }
            $content[$flag] = $value;
            \file_put_contents($path, \json_encode($content));
        }
    }
}
