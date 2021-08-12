<?php


namespace didix16\Api\ApiDataMapper;


use didix16\Api\ApiDataObject\ApiDataObjectInterface;
use ReflectionClass;

/**
 * Class ApiDataMapper
 * @package didix16\Api\ApiDataMapper
 */
abstract class ApiDataMapper implements ApiDataMapperInterface
{

    /**
     * Given A model class and an ApiDataObject, attempt to generate an instance of $modelClass with data given
     * @param $modelClass - Should be any kind of ORM entity or object class representing a model in DDBB
     * @param ApiDataObjectInterface $data
     * @return object
     * @throws ApiDataMapperException
     */
    public function mapToModel($modelClass, ApiDataObjectInterface $data): object
    {
        throw new ApiDataMapperException("This method should be implemented on child class");
    }

    /**
     * Given a model class and ApiDataObjectInterface, attempts to generate an interable of
     * $modelClass with data given
     * @param $modelClass - Should be any kind of ORM entity or object class representing a model in DDBB
     * @param ApiDataObjectInterface $data
     * @return iterable
     * @throws ApiDataMapperException
     */
    public function mapToModelList($modelClass, ApiDataObjectInterface $data): iterable
    {
        throw new ApiDataMapperException("This method should be implemented on child class");
    }

    /**
     * Given an instance of a model and an ApiDataObjectInterface, attempt to refresh the model with data given
     * @param object $instance
     * @param ApiDataObjectInterface $data
     * @throws ApiDataMapperException
     */
    public function refreshModel(object $instance, ApiDataObjectInterface $data): void {
        throw new ApiDataMapperException("This method should be implemented on child class");
    }

    /**
     * Refreshes instance $to using instance $from
     * If $strict is true and the instances are not the same class then an exception is thrown
     * @param object $to,
     * @param object $from
     * @param bool $strict
     * @throws ApiDataMapperException
     */
    public function refreshModelFromOtherModel(object $to, object $from, bool $strict = false): void
    {
        throw new ApiDataMapperException("This method should be implemented on child class");
    }

    /**
     * Try to build a model class instance without params
     * If some error occurs then returns null
     * @param $modelClass
     * @return mixed
     */
    protected function makeModelInstance($modelClass){

        try {

            $reflectedClass = new ReflectionClass($modelClass);

            $constructor = $reflectedClass->getConstructor();
            if ($constructor && $constructor->getNumberOfParameters() === 0)
                return $reflectedClass->newInstance();
            
            return $reflectedClass->newInstanceWithoutConstructor();
            
        } catch (\Throwable $e) {
            var_dump($e);
            return null;
        }

    }

    /**
     * Applies the data mapping to a model instance and returns the instance with the specified properties filled
     * @param $instance
     * @param ModelMapInterface $modelMap
     * @param ApiDataObjectInterface $data
     * @return mixed
     */
    protected function applyModelMap($instance, ModelMapInterface $modelMap, ApiDataObjectInterface $data) {

        return $modelMap($instance, $data);
    }

    /**
     * Same as applyModelMap method but returns a list of hydrated $instances instead of returning a single one
     * @param $instance
     * @param ModelMapInterface $modelMap
     * @param ApiDataObjectInterface $data
     * @return iterable
     */
    protected function applyModelMapList($instance, ModelMapInterface $modelMap, ApiDataObjectInterface $data): iterable
    {
        return $modelMap($instance, $data);
    }

}