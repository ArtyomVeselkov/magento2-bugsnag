<?php
/**
 *  Copyright © 2018 Optimlight. All rights reserved.
 *  See LICENSE.txt for license details.
 */
namespace Optimlight\Bugsnag\Model\Pipeline;

/**
 * Interface PipelineBuilderInterface
 * @package Optimlight\Bugsnag\Model\Pipeline
 */
interface PipelineBuilderInterface
{
    /**
     * @param StageInterface $stage
     *
     * @return $this
     */
    public function add(StageInterface $stage);

    /**
     * @param ProcessorInterface|null $processor
     *
     * @return PipelineInterface
     */
    public function build(ProcessorInterface $processor = null);
}
