<?php


namespace didix16\Api\ApiDataMapper;

/**
 * Class ModelMapFunction
 * @package didix16\Api\ApiDataMapper
 */
abstract class ModelMapFunction implements ModelMapfunctionInterface
{
    /**
     * The name given to this function
     * @var string
     */
    protected $name;

    public function __construct($name = "")
    {
        if (empty($name)){
            $this->name = $this->getNameByClassName();
        }else{
            $this->name = $name;
        }

    }

    /**
     * Get the name for this function by its class name, removing the MapFunction at the end of string and
     * case lowering the first letter
     * @return string
     */
    protected function getNameByClassName(){

        $class = get_class($this);
        $name =  substr($class, strrpos($class, '\\') + 1);
        return lcfirst(str_replace("MapFunction", '', $name));

    }

    public function getName(): string {

        return $this->name;
    }

    /**
     * Code to run when called
     * Usually:
     *  $args[0] should be the value being processed
     *  $args[1] the ApiDataObject with whole data
     *  $args[2] the modelField name to set the data
     * @param $args
     * @return mixed
     */
    protected abstract function run(...$args);

    /**
     * Execute code on this();
     * @param array $args
     * @return mixed
     */
    public function __invoke(...$args)
    {
        return $this->run(...$args);
    }
}