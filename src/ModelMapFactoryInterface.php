<?php


namespace didix16\Api\ApiDataMapper;

/**
 * Interface ModelMapFactoryInterface
 * @package didix16\Api\ApiDataMapper
 */
interface ModelMapFactoryInterface
{
    /**
     * Given a model class, try to build its corresponding ModelMap
     * @param $modelClass
     * @return ModelMapInterface
     */
    public static function build($modelClass): ModelMapInterface;
}