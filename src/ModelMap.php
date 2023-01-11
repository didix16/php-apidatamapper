<?php


namespace didix16\Api\ApiDataMapper;

use didix16\Api\ApiDataMapper\FieldGrammar\FieldLexer;
use didix16\Api\ApiDataMapper\FieldGrammar\FieldParser;
use didix16\Api\ApiDataMapper\FieldInterpreter\FieldInterpreter;
use didix16\Api\ApiDataMapper\FieldInterpreter\Filters\FieldFilter;
use didix16\Api\ApiDataMapper\FieldInterpreter\Functions\AggregateFunction;
use didix16\Api\ApiDataObject\ApiDataObject;
use didix16\Api\ApiDataObject\ApiDataObjectInterface;
use didix16\Hydrator\HydratorInterface;
use didix16\Hydrator\ReflectionHydrator;
use Exception;
use ReflectionException;

/**
 * Class ModelMap - A class that handles the mapping conversion between external data and system entities/models
 * @package didix16\Api\ApiDataMapper
 */
class ModelMap implements ModelMapInterface
{

    /**
     * The model class which external data needs to be mapped
     * @var string
     */
    protected string $modelClass;

    /**
     * Contains the field mapping from externalFields to model fields
     * @var array
     */
    private array $fieldMap = [];

    /**
     * If model map is multiple then should contains the field list mapping from
     * $this->arrayField.externalFields to model fields
     */
    private array $fieldListMap = [];

    /**
     * A list of fields that should be ignored if model instance already has a value
     * different from null
     * @var array
     */
    private array $ignoreFields = [];

    /**
     * A map that each key is a filter name and value is a FieldFilter
     * Used to be loaded into a FieldInterpreter
     * @var FieldFilter[]
     */
    private array $fieldFilters = [];

    /**
     * A map that each key is an aggregate function name and value is an AggregateFunction
     * Used to be loaded into a FieldInterpreter
     * @var AggregateFunction[]
     */
    private array $aggrefateFunctions = [];

    /**
     * A map that each key is a model field name and value is a ModelMapFunction
     * @var array
     */
    private array $fieldFunctions = [];

    /**
     * The hydrator used to fill the given model properties
     * @var HydratorInterface;
     */
    private HydratorInterface $dataHydrator;

    /**
     * A flag indicating if multiple instances must be generated instead of only one
     * @val bool
     */
    private bool $multiple = false;

    /**
     * External field name which is an array
     * @var string
     */
    private string $arrayField = "";

    public function __construct(?HydratorInterface $hydrator = null)
    {
        if(!$hydrator)
            $this->dataHydrator = new ReflectionHydrator();
        else
            $this->dataHydrator = $hydrator;
    }

    /**
     * Tell to this model map that should generate multiple instances by using $arrayField as field list
     * @param string $arrayField
     * @return ModelMap
     */
    public function setMultiple(string $arrayField): self
    {
        $this->arrayField = $arrayField;
        $this->multiple = true;

        return $this;
    }

    /**
     * Tell to this model map that don't generate multiple instances.
     * This methods unset the arrayField if was stablished using #setMultiple method
     */
    public function unsetMultiple(): self
    {
        $this->arrayField = '';
        $this->multiple = false;

        return $this;
    }

    /**
     * Check if this model map is configured to process and return a multiple instances
     */
    public function isMultiple(): bool
    {
        return $this->multiple && $this->arrayField !== "";
    }

    /**
     * Given $data, check if this model map is configured ti be multiple.
     * If it is, then returns an interable data specified by $arrayField set on setMultiple method
     * else, wrapps $data into an array
     */
    protected function getListData(ApiDataObjectInterface $data): iterable
    {
        if(!$this->isMultiple())
            return $this->toArray($data);

        $fieldLexer = new FieldLexer($this->arrayField);
        $fieldParser = new FieldParser($fieldLexer);
        $fieldInterpreter = new FieldInterpreter($fieldParser, $data);
        $processedField = $fieldInterpreter->run();

        return $processedField[$this->arrayField];
    }

    /**
     * Wrapps the given data into an array if $data is not already an array
     */
    protected function toArray($data)
    {
        return ! is_array($data) ? [$data] : $data;
    }

    /**
     * Given a model field and an external field, makes an association from external field to $model field
     *
     * Notes:
     *  1) The external data may need a transformation pipeline
     *  2) The origin data may be nested in different 'levels'
     *  3) The origin data may be atomic or composed by a set of atomic items
     *
     * So, data may be transformed and origin data field may be simple or complex ( set of items )
     * Thus a process to handle that is needed. see __invoke($instance)
     * @param $modelField
     * @param $externalField
     * @return $this
     */
    protected function mapField($modelField, $externalField){

        $this->fieldMap[$externalField] = $modelField;
        return $this;
    }

    /**
     * Same as mapField but this is only used if map should generate an interable of instances.
     * The specified field should be inside an object array of kind data[$this->arrayField] 
     */
    protected function mapListField($modelField, $externalField)
    {
        $this->fieldListMap[$externalField] = $modelField;

        return $this;
    }

    /**
     * Given a model field names, ignores the fields on field interpreting process if model instance
     * field has value different from null or empty
     * @param iterable|string $fields
     * @return $this
     */
    public function ignoreFieldsIfSet($fields): ModelMap
    {

        $fields = is_array($fields) ? $fields: [$fields];

        foreach ($fields as $field){

            $this->ignoreFields[$field] = $field;
        }

        return $this;
    }

    /**
     * Given model fields, remove from ignore field list, the specified model fields
     * @param $fields
     * @return $this
     */
    public function unignoreFieldsIfSet($fields): ModelMap
    {

        $fields = is_array($fields) ? $fields: [$fields];

        foreach ($fields as $field){

            unset($this->ignoreFields[$field]);
        }

        return $this;
    }

    /**
     * Given a model instance, just returns a deep copy if that instance
     * @param object $modelInstance
     */
    protected function cloneInstance(object $modelInstance): object
    {
        return clone $modelInstance;
    }

    /**
     * Given an associative array with key as externalField and a value as modelField,
     * tries to make the association for this model map
     * @param array $fieldMap
     * @return $this
     * @throws Exception
     */
    public function mapFields(array $fieldMap) {

        $this->fieldMap = [];

        foreach ($fieldMap as $externalField => $modelField){

            $this->mapField($modelField, $externalField);
        }

        return $this;
    }

    /**
     * Same as mapFields but for list fields
     */
    public function mapListFields(array $listFieldMap): self
    {
        $this->fieldListMap = [];

        foreach($listFieldMap as $externalField => $modelField){

            $this->mapListField($modelField, $externalField);
        }

        return $this;
    }

    /**
     * Allows to register external components to extends the functionality of the model map language
     * The components allowed are: FieldFilter, AggregateFunction and ModelMapFunction
     * @param FieldFilter|FieldFilter[]|AggregateFunction|AggregateFunction[]|ModelMapFunction|ModelMapFunction[] $components
     */
    public function use($components): self
    {   
        $isArray = is_array($components);
        if($isArray && empty($components)){
            return $this;
        } else if(!$isArray) {
            $components = [$components];
        }

        foreach($components as $component){

            switch(true){
                case $component instanceof FieldFilter:
                    $this->registerFieldFilter($component);
                    break;
                case $component instanceof AggregateFunction:
                    $this->registerAggregateFunction($component);
                    break;
                case $component instanceof ModelMapFunction:
                    $this->registerFieldFunction($component);
                    break;
                default:
                    continue 2;
            }
        }

        return $this;
    }

    /**
     * Given a FieldFilter, register it to this model map to be used by FieldInterpreter
     */
    protected function registerFieldFilter(FieldFilter $filter): self
    {
        if (!isset($this->fieldFilters[$filter->getName()])) {
            $this->fieldFilters[$filter->getName()] = $filter;
        }

        return $this;
    }

    /**
     * Given an AggregateFunctiion, register it to this model map to be used by FieldInterpreter
     */
    protected function registerAggregateFunction(AggregateFunction $function)
    {
        if (!isset($this->aggrefateFunctions[$function->getName()])) {
            $this->aggrefateFunctions[$function->getName()] = $function;
        }

        return $this;
    }

    /**
     * Given a field interpreter, loads the registered field filters of this model map to the interpreter
     */
    protected function loadFieldFilters(FieldInterpreter $fieldInterpreter): self
    {
        foreach($this->fieldFilters as $filter){

            $fieldInterpreter->loadFilter($filter);
        }

        return $this;
    }

    /**
     * Given a field interpreter, loads the registered aggregate functions of this model map to the interpreter
     */
    protected function loadAggregateFunctions(FieldInterpreter $fieldInterpreter): self
    {
        foreach($this->aggrefateFunctions as $function){

            $fieldInterpreter->loadFunction($function);
        }

        return $this;
    }

    /**
     * Given a ModelMapfunction, register the model map function for this model map
     * @param ModelMapfunctionInterface $mapfunction
     * @return ModelMap
     */
    protected function registerFieldFunction(ModelMapfunctionInterface $mapfunction): self
    {

        if( !isset($this->fieldFunctions[$mapfunction->getName()]) ) {
            $this->fieldFunctions[$mapfunction->getName()] = $mapfunction;
        }

        return $this;
    }

    /**
     * Transforms the external data.
     * If this model map is configured as multiple then will generate an interable of hydrated $modelInstances
     * Usefull to map object arrays.
     * @param object $modelInstance
     * @param ApiDataObjectInterface $data
     * @return object|iterable
     * @throws ReflectionException
     * @throws Exception
     */
    public function __invoke(object $modelInstance, ApiDataObjectInterface $data)
    {
        if(!$this->isMultiple()){
           $this->transformData($modelInstance, $data);
           return $modelInstance;
        } else {

            $this->hydratedModels = [];
            $dataList = $this->getListData($data);

            foreach($dataList as $d){

                $model = $this->cloneInstance($modelInstance);
                $this->hydratedModels[] = $this->transformData($model, $data, $d);
            }

            return $this->hydratedModels;
        }
    }

    /**
     * Transforms the external data if needed and sets the $modelInstance fields that comes from $data
     * @param object $modelInstance
     * @param ApiDataObjectInterface $data
     * @param object|null $listObject
     * @return object
     * @throws ReflectionException
     * @throws Exception
     */
    private function transformData(object $modelInstance, ApiDataObjectInterface $data, object $listObject = null)
    {
        // Field data which key is a model property and vale the desired value that we want the property must have
        $fieldData = [];

        $modelClass = new \ReflectionClass(get_class($modelInstance));

        if($listObject){

            foreach($this->fieldListMap as $externalField => $modelField){

                /**
                 * Check if model field has a modelFieldFunction to be called before final property assignment
                 */
                list($modelField, $modelFieldFunction) = $this->getModelFieldParts($modelField);

                /**
                 * Check if we have to ignore field.
                 */
                $reflectionField = $modelClass->getProperty($modelField);

                if ($reflectionField->isInitialized($modelInstance))
                    $value = $reflectionField->getValue($modelInstance);
                else
                    $value = null;

                if (isset($this->ignoreFields[$modelField]) && !is_null($value) && $value !== '' )
                    continue;

                /**
                 * Transform pipeline
                 */
                $fieldLexer = new FieldLexer($externalField);
                $fieldParser = new FieldParser($fieldLexer);
                $fieldInterpreter = new FieldInterpreter($fieldParser, $listObject);
                $this
                    ->loadAggregateFunctions($fieldInterpreter)
                    ->loadFieldFilters($fieldInterpreter);
                $processedField = $fieldInterpreter->run();

                if(! ApiDataObject::isUndefined($processedField[$externalField])){

                    // Need the processedField be processed by a modelFieldFunction ?
                    if($modelFieldFunction){
                        $processedField[$externalField] = $this->callFunction(
                            $modelFieldFunction,
                            $processedField[$externalField],
                            $listObject,
                            $modelField
                        );
                    }

                    $fieldData[$modelField] = $processedField[$externalField];
                } else
                    unset($processedField);
            }
        }

        foreach($this->fieldMap as $externalField => $modelField){

            /**
             * Check if model field has a modelFieldFunction to be called before final property assignment
             */
            list($modelField, $modelFieldFunction) = $this->getModelFieldParts($modelField);

            /**
             * Check if we have to ignore field.
             */
            $reflectionField = $modelClass->getProperty($modelField);

            if ($reflectionField->isInitialized($modelInstance))
                $value = $reflectionField->getValue($modelInstance);
            else
                $value = null;

            if (isset($this->ignoreFields[$modelField]) && !is_null($value) && $value !== '' )
                continue;

            /**
             * Transformation pipeline
             */
            $fieldLexer = new FieldLexer($externalField);
            $fieldParser = new FieldParser($fieldLexer);
            $fieldInterpreter = new FieldInterpreter($fieldParser, $data);
            $this
                    ->loadAggregateFunctions($fieldInterpreter)
                    ->loadFieldFilters($fieldInterpreter);
            $processedField = $fieldInterpreter->run();

            if(! ApiDataObject::isUndefined($processedField[$externalField])){

                // Need the processedField be processed by a modelFieldFunction ?
                if($modelFieldFunction){

                    $processedField[$externalField] = $this->callFunction(
                        $modelFieldFunction,
                        $processedField[$externalField],
                        $data,
                        $modelField
                    );
                }

            
                $fieldData[$modelField] = $processedField[$externalField];
            } else
                unset($processedField);

        }

        // Hydrate model instance by giving the fieldData array to it
        $this->dataHydrator->hydrate($fieldData, $modelInstance);

        return $modelInstance;
    }

    /**
     * Calls the given function name on this model map with $value field, entire APiDataObject
     * and modelfield as arguments
     * @param $functionName
     * @param $value
     * @param ApiDataObjectInterface|object $apiDataObject
     * @param string $modelField
     * @return mixed
     * @throws Exception
     */
    private function callFunction($functionName, &$value, object $apiDataObject, string $modelField){

        if( isset($this->fieldFunctions[$functionName]) ) {
            return $this->fieldFunctions[$functionName]($value, $apiDataObject, $modelField);
        }else{
            throw new \Exception("Method [$functionName] does not exists on this model map. Did you registered?");
        }

    }

    /**
     * Given a model field of form field:function, returns an array like ['field', 'function' | null ]
     * @param $modelField
     * @return array
     */
    private function getModelFieldParts($modelField): array {

        $modelFieldParts = explode(":", $modelField);
        $modelField = $modelFieldParts[0];
        $modelFieldFunction = $modelFieldParts[1] ?? null;

        return [$modelField, $modelFieldFunction];
    }

}