<?php

namespace didix16\examples;

use didix16\Api\ApiDataMapper\ModelMapFactoryInterface;
use didix16\Api\ApiDataMapper\ModelMapInterface;

class ModelMapFactory implements ModelMapFactoryInterface
{   
    /**
     * Given a $modelClass, returns a new instance of a ModelMapInterface
     * that is related to the $modelClass
     */
    public static function build($modelClass): ModelMapInterface
    {
        switch($modelClass){
            case Warrior::class:
                return new WarriorModelMap();
            case Monster::class:
                    return new MonsterModelMap();
            default:
                throw new \Exception(sprintf('There are not factory for class %s', $modelClass));
        }
    }
}