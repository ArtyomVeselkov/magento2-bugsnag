<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Optimlight\Bugsnag\Model\Deploy;

/**
 * Class Autoloader
 * @package Optimlight\Bugsnag\Model\Deploy
 */
class Autoloader extends AbstractDeploy
{
    /**
     * @param bool $canDeploy
     * @return bool
     */
    public function exec($canDeploy = false)
    {
        $result = false;
        if (!$canDeploy) {
            return $result;
        }
        $map = $this->getMap();
        if (is_array($map)) {
            $bp = $this->getBasePath();
            $mp = $this->getModulePath();
            foreach ($map as $pair) {
                if (is_array($pair) && 1 < count($pair)) {
                    $from = $mp . $pair[0];
                    // What is better here: $bp or $mp?
                    // $to = $bp /* strip '../../../' */. $pair[1];
                    $to = $mp . $pair[1];
                    if (!file_exists($to) && file_exists($from)) {
                        @copy($from, $to);
                    }
                }
            }
            $result = true;
        }
        return $result;
    }

    /**
     * @return array
     */
    private function getMap()
    {
        $package = $this->getPackage();
        $result = [];
        if ($package) {
            $extra = $package->getExtra();
            if (is_array($extra) && isset($extra['map'])) {
                $result = is_array($extra['map']) ? $extra['map'] : [];
            }
        }
        return $result;
    }

    /**
     * @return string
     */
    private function getBasePath()
    {
        return BP . DIRECTORY_SEPARATOR;
    }

    /**
     * @return string
     */
    private function getModulePath()
    {
        return realpath(
                dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..'
            ) . DIRECTORY_SEPARATOR;
    }
}
