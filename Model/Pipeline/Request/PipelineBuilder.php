<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Optimlight\Bugsnag\Model\Pipeline\Request;

use Optimlight\Bugsnag\Model\Pipeline\{
    PipelineInterface,
    PipelineBuilderInterface,
    ProcessorInterface,
    Pipeline,
    StageInterface
};

/**
 * Class PipelineBuilder
 * @package Optimlight\Bugsnag\Model\Pipeline\Request
 */
class PipelineBuilder implements PipelineBuilderInterface
{
    /**
     * @var StageInterface[]
     */
    private $stages = [];

    /**
     * @param StageInterface $stage
     *
     * @return $this
     */
    public function add(StageInterface $stage)
    {
        $this->stages[] = $stage;

        return $this;
    }

    /**
     * @param ProcessorInterface|null $processor
     *
     * @return PipelineInterface
     */
    public function build(ProcessorInterface $processor = null)
    {
        return new Pipeline($processor, $this->stages);
    }
}
