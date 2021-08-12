<?php


namespace didix16\Api\ApiDataMapper;

use didix16\Api\ApiDataObject\ApiDataObjectInterface;
use didix16\Hydrator\HydratorInterface;
use didix16\Api\ApiDataMapper\HasModelMapFactory;

/**
 * Class GlobalApiDataMapper - A class that handles generic DTO mapping to any entity
 * @package didix16\Api\ApiDataMapper
 */
class GlobalApiDataMapper extends ApiDataMapper {

    use HasModelMapFactory;

    /**
     * @var HydratorInterface
     */
    protected HydratorInterface $hydrator;

    public function __construct(ModelMapFactoryInterface $factory, HydratorInterface $hydrator)
    {
        $this->modelMapFactory = $factory;
        $this->hydrator = $hydrator;
    }

    public function mapToModel($modelClass, ApiDataObjectInterface $data): object
    {
        $model = $this->makeModelInstance($modelClass);
        $this->applyModelMap($model, $this->getOrBuildMap($modelClass), $data);

        return $model;
    }

    public function refreshModel(object $instance, ApiDataObjectInterface $data): void
    {
        $class = get_class($instance);
        $modelMap = $this->getOrBuildMap($class);

        if(!$modelMap->isMultiple())
            $this->applyModelMap($instance, $modelMap, $data);
        else
            throw new ApiDataMapperException(
                sprintf("
                    The model %s cannot be refreshed this way since its model map is configured to be multiple\n
                    You should use refreshModelFromOtherModel instead.
                ", $class)
            );
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
        $class1 = get_class($to);
        $class2 = get_class($from);
        if($strict && $class1 !== $class2){
            throw new ApiDataMapperException(
                sprintf(
                    "The two model classes must be the same.\n",
                    "First param class is %s and the second is %s",
                    $class1, $class2
                )
            );
        }
            
        $data = $this->hydrator->extract($from);
        $this->hydrator->hydrate($data, $to);

    }

    public function mapToModelList($modelClass, ApiDataObjectInterface $data): iterable
    {
        $model = $this->makeModelInstance($modelClass);
        return $this->applyModelMapList($model, $this->getOrBuildMap($modelClass), $data);
    }
}