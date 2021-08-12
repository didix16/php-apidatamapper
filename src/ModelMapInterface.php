<?php


namespace didix16\Api\ApiDataMapper;


use didix16\Api\ApiDataObject\ApiDataObjectInterface;

/**
 * Interface ModelMapInterface
 * @package didix16\Api\ApiDataMapper
 */
interface ModelMapInterface
{
    /**
     * Given an associative array with key as externalField and a value as modelField,
     * tries to make the association for this model map
     * @param array $fieldMap
     * @return $this
     */
    public function mapFields(array $fieldMap);

    /**
     * Same as mapFields but for list fields
     * @param array $listFieldMap
     * @return $this;
     */
    public function mapListFields(array $listFieldMap): self;

    /**
     * Check if this model map is configured to process and return multiple instances
     */
    public function isMultiple(): bool;

    /**
     * Transforms the external data if needed and sets the $modelInstance fields that comes from $data;
     * @param object $modelInstance
     * @param ApiDataObjectInterface $data
     * @return mixed
     */
    public function __invoke(object $modelInstance, ApiDataObjectInterface $data);
}