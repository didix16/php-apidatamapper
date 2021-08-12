<?php


namespace didix16\Api\ApiDataMapper;


use didix16\Api\ApiDataObject\ApiDataObjectInterface;

/**
 * Interface ApiDataMapperInterface
 * @package didix16\Api\ApiDataMapper
 */
interface ApiDataMapperInterface
{
    /**
     * Given A model class and an ApiDataObjectInterface, attempt to generate an instance of
     * $modelClass with data given
     * @param $modelClass - Should be any kind of ORM entity or object class representing a model in DDBB
     * @param ApiDataObjectInterface $data
     * @return mixed
     */
    public function mapToModel($modelClass, ApiDataObjectInterface $data): object;

    /**
     * Given a model class and ApiDataObjectInterface, attempts to generate an interable of
     * $modelClass with data given
     * @param $modelClass - Should be any kind of ORM entity or object class representing a model in DDBB
     * @param ApiDataObjectInterface $data
     * @return iterable
     */
    public function mapToModelList($modelClass, ApiDataObjectInterface $data): iterable;

    /**
     * Given an instance of a model and an ApiDataObjectInterface, attempt to refresh the model with data given
     * @param object $instance
     * @param ApiDataObjectInterface $data
     */
    public function refreshModel(object $instance, ApiDataObjectInterface $data): void;

     /**
     * Refreshes instance $to using instance $from
     * If $strict is true and the instances are not the same class then an exception is thrown
     * @param object $to,
     * @param object $from
     * @param bool $strict
     * @throws ApiDataMapperException
     */
    public function refreshModelFromOtherModel(object $to, object $from, bool $strict = false): void;
}