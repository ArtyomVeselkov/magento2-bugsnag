<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Optimlight\Bugsnag\Model\Builder;

use Optimlight\Bugsnag\Model\BuildableInterface;
use Optimlight\Bugsnag\Model\Resolver\Build\BuildInterface;

/**
 * Interface BuilderInterface
 * @package Optimlight\Bugsnag\Model\Builder
 */
interface BuilderInterface
{
    /**
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function setArgument($name, $value);

    /**
     * @param array $map
     * @return $this
     */
    public function setMap($map);

    /**
     * @return BuildableInterface
     */
    public function build();
}
