<?php

namespace didix16\Api\ApiDataMapper;

use didix16\Api\ApiDataMapper\ModelMapFactoryInterface;
use didix16\Api\ApiDataMapper\ModelMapInterface;
use didix16\Api\ApiDataMapper\HighOrderModelMapConfiguration;

trait HasModelMapFactory {

    /**
     * @var ModelMapInterface[] $mapClasses An association between classes and its ModelMap
     */
    static array $mapClasses = [];

    /**
     * @var ModelMapFactoryInterface
     */
    protected ModelMapFactoryInterface $modelMapFactory;

    /**
     * Builds a new ModelMap of class $modelClass
     */
    public function make($modelClass): ModelMapInterface {

        return $this->modelMapFactory::build($modelClass);
    }

    /**
     * Given a $modelClass, returns its model map
     * If does not exists yet, it creates the model map and returns it
     */
    protected function getOrBuildMap($modelClass): ModelMapInterface {

        if(!isset(static::$mapClasses[$modelClass]))
            static::$mapClasses[$modelClass] = $this->make($modelClass);
        return static::$mapClasses[$modelClass];
    }

    /**
     * Allows to access the model map for specified $modelClass
     * Returns a HighOrderModelMapConfiguration to allow chain access
     * between model map and api data mapper
     * @param string $modelClass
     * @return HighOrderModelMapConfiguration
     */
    public function configure(string $modelClass): HighOrderModelMapConfiguration
    {
        if(!isset(static::$mapClasses[$modelClass]))
            static::$mapClasses[$modelClass] = $this->make($modelClass);

        return new HighOrderModelMapConfiguration($this, static::$mapClasses[$modelClass]);
        
    }
}