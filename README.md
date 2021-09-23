PHP API DataMapper
=

An extensible DTO library that allows map incoming API data to any of your entities/models by using a simple filed mapping language with filters and functions.


# Content

- [What is an API DataMapper](#what-is-an-api-datamapper)
- [What is a Model Map](#what-is-a-model-map)
- [Installation](#installation)
- [Usage](#usage)
    - [Field Language](#field-language)
    - [FieldInterpreter](#field-interpreter)
        - [FieldFilter](#fieldfilter)
        - [FieldFunction](#fieldfunction)
    - [ModelMap](#modelmap)
        - [ModelMapFunction](#modelmapfunction)
        - [ModelMapFactory](#modelmapfactory)
    - [ApiDataObject](#apidataobject)
    - [ApiDataMapper](#apidatamapper)

- [Examples](#examples)

## What is an API DataMapper

An API DataMapper is a class that allows to map incoming data to a model or entity class you have without much effort. The only have to do is instruct which incoming fields should be mapped to you entity class fields and how. In other words, to allows to use the DTO pattern with every class you have.

It is able to preprocess data and transform it before the final data is set to an entity field.

## What is a Model Map

So a model map is a class that handle all the tough stuff of mapping an incoming data into a model of your application. It has the configuration of which fields should be mappend into which ones and how. This is where all DTO magic happens. It uses a [FieldInterpreter](src/FieldInterpreter/FieldInterpreter.php) to archive this task.

## Installation

```php
composer require didix16/php-apidatamapper
```

## Usage

In the following list you will see how to use each important part of this package:

### Field language

This is the language that is being used by a model mapper. It syntax is very easy to understand: it allows to select which field do you want to extract and process from an [APIDataObject](#apidataobject).

The syntax allows to select a single field from an object, a field from a list (array, vector, whatever..)and transform incoming data ( i.e string to PHP DateTime). Also allows the use of aggregate functions for list items. For example, get the Maximum value of certain amount inside a list.

#### SYNTAX

Supose we have this JSON $data

```json
{
    "warrior": {
        "name": "Lancelot",
        "active": "no",
        "weapon": "Spear",
        "comes_from": "Camelot"
    }
}
```

Select first lvl field: warrior
```php

use didix16\Api\ApiDataObject\ApiDataObject;

class MyApiDataObject extends ApiDataObject {}


$data = MyApiDataObject::fromJson($data);

$input = "warrior";


$fi = new FieldInterpreter($parser, $data);
$lexer = new FieldLexer($input);
$parser = new FieldParser($lexer);

$res = $fi->run();

var_dump($res);
/**
 * array(1) {
 *    ["warrior"]=> stdClass(warrior...)
 *    
 * }
 * 
 */

```

Select specific field from object and apply a filter
( in this case BooleanFilter)


```php

use didix16\Api\ApiDataObject\ApiDataObject;

class MyApiDataObject extends ApiDataObject {}

/**
 * Example of how to parse a field from api data and turn into a boolean
 */
$input = "warrior.active:boolean";
$lexer = new FieldLexer($input);
$parser = new FieldParser($lexer);

$data = MyApiDataObject::fromJson($data);

$fi = new FieldInterpreter($parser, $data);

$res = $fi->run();

var_dump($res);
/**
 * array(1) {
 *    ["warrior.active:boolean"]=>
 *    bool(false)
 * }
 * 
 */
```

There is "no limit" on depth to select a deepest property.

```
warrior.place.country.address.name ...
```

We can stack filters as much as we need.

For example:

```
warrior.comes_from:capitalize,snakecase,kebab,...
```

NOTE: those filter does not exists, you have to register them. In sections below you will see how.

The transformation pipeline order follows the specified in syntax, from left to right. In the example above, capitalize filter will be executed first, then snakecase, and so on...


Now, supose we have this JSON $data

```json
{
    "comes_from": "Camelot",
    "warrior_list": [
        {
            "name": "Lancelot",
            "active": "no",
            "weapon": "Spear",
            "kills": 90
            
        },
        {
            "name": "Arthur",
            "active": "yes",
            "weapon": "Sword",
            "kills": 50
        },
        ...
}

```
Select a field inside a list:

```
warrior_list[].name
```

```php
$data = MyApiDataObject::fromJson($data);

$input = "warrior_list[].name";
$lexer = new FieldLexer($input);
$parser = new FieldParser($lexer);
$fi = new FieldInterpreter($parser, $data);

$res = $fi->run();

var_dump($res);
/**
 * array(1) {
 * ["warrior_list[].name"]=>
 * array(2) {
 *   [0]=>
 *   string(8) "Lancelot"
 *   [1]=>
 *   string(6) "Arthur"
 * }
 *}
 * 
 */
```

Use an aggregate function inside a list:

```
MAX(warrior_list[].kills)
```
```php
$input = "MAX(warrior_list[].kills)";
$lexer = new FieldLexer($input);
$parser = new FieldParser($lexer);
$fi = new FieldInterpreter($parser, $data);

$res = $fi->run();

var_dump($res);

/**
 * array(1) {
 * ["MAX(warrior_list[].kills)"]=>
 * int(90)
 *}
 */
```

NOTE: At the moment only MAX and MIN are available. However, you can extend the field language by adding the functions you need like: SUM, AVG, MEAN, etc...

BTW: You can only use aggregate functions with list fields inside! I.e: MAX(name) won't work, but MAX(warrior_list[].name) yep.

### FieldInterpreter

As we saw above, to make FieldInterpreter do its work, we need a [FieldLexer](src/FieldGrammar/FieldLexer.php) and [FieldParser](src/FieldGrammar/FieldParser.php).

The FieldLexer receives the Field Language input syntax. The FieldParser receives the FieldLexer as the only argument.

Finally you have to pass the FieldParser to FieldInterpreter and an APIDataObject instance to make its magic :).

If for some reason the syntax is invalid, you will get an Exception.

Finally, you need to call the #run method from the interpreter. The result will be an associative array
where the key is the processed input and value the final processed data.

```php
$input = "MAX(warrior_list[].kills)";
$lexer = new FieldLexer($input);
$parser = new FieldParser($lexer);
$fi = new FieldInterpreter($parser, $data);

$res = $fi->run(); // returns an array
```

NOTE: If you specify a field which is not coming in incoming data, then the value for that field will be:
```php
class didix16\Api\ApiDataObject\UndefinedField {}
```

So you must check with **ApiDataObject::isUndefined($res\[$field\])** if the result is correct or is an undefined field.


#### FieldFilter

As we saw, filters allows to pipeline data and transform it. We can add filters as much as we need.

By default there are two filters: DateFilter and BooleanFilter.

BooleanFilter transforms potential values to be casted as boolean value. For example: "yes", 1, "1", "true", true, "True", "tRUE", and so on... will be transformed to PHP true value. However, "no", 0, "1", "false", false, "FALSE", "fAlse" and so on... will be transformed into PHP false value.

You can instante a new BooleanFilter with an associative array that tells the filter which values should be treat as true and which as false:

```php
// The second parameter is $forceFalse.
// If is true then if the value founded is not in the specified list nor is a php boolean value the value will be set to false as default.
// By default is false and thus will leave the value as is if is "non-booleable"
$filter = new BooleanFilter(
[
    "true" => [
        "done",
        "completed",
        ...
    ],
    "false" => [
        "pending",
        "not_finished",
        ...
    ]
], true);
```

DateFilter allows to parse any standard date formats into a PHP DateTime class.

First of all, it will try to transform by testing each of these formats:

```php
DateTimeInterface::ATOM,
DateTimeInterface::COOKIE,
DateTimeInterface::ISO8601,
DateTimeInterface::RFC822,
DateTimeInterface::RFC850,
DateTimeInterface::RFC1036,
DateTimeInterface::RFC1123,
DateTimeInterface::RFC2822,
DateTimeInterface::RFC3339,
DateTimeInterface::RFC3339_EXTENDED,
DateTimeInterface::RSS,
DateTimeInterface::W3C
```
If none of them were found, then $fromFormat='Y-m-d' constructor option will be used as last chance.

Also you can pass a timezone as a second optional argument

```php
//                     $fromFormat   $toTimezone
$filter = new DateFilter('d-m-Y', 'Europe/London');
```

Feel free to extend both if you need.

To make your own filter you need to extend from:
```php
class  didix16\Api\ApiDataMapper\FieldInterpreter\Filters\FieldFilter;
```

Finally, to register a filter you need to call #loadFilter(FieldFilter $filter) method from FieldInterpreter BEFORE call #run method

```php

/**
 * Custom FieldField: allows capitilize strings
 */
class CapitalizeFilter extends FieldFilter {

    protected function transform(&$value)
    {
        if($this->assertString($value))
            $value = strtoupper($value);
    }
}

/**
 * Custom FieldFilter: adds '_thisIsASuffix' as a string suffix
 */
class SuffixerFilter extends FieldFilter {

    protected function transform(&$value)
    {
        if($this->assertString($value))
            $value = $value . '_thisIsASuffix';
    }
}

$input = "warrior.name:capitalize,suffixer";
...

$fi = new FieldInterpreter($parser, $data);
$fi
    ->loadFilter(new CapitalizeFilter('capitalize'))
    ->loadFilter(new SuffixerFilter('suffixer'));
```

IMPORTANT: filters name MUST BE the same in field language syntax. If you named "capitalize" your filter, then in $input syntax, the filter should be "capitalize" as well.

FieldFilter receive as mandatory parameter its name. If you look inside BooleanFilter and DateFilter, you will see that there is a 
```php
parent::__construct("boolean")
```
and
```php
parent::__construct("date")
```
 lines respectively inside their constructors

#### FieldFunction

Like FieldFilter but for AggregateFunctions.
As I wrote before, aggregate functions only works with lists, so be carefully.

There are MAX and MIN functions ( self explanatory).

If you need to add your own functions, you have to extend

```php
class  didix16\Api\ApiDataMapper\FieldInterpreter\Functions\AggregateFunction;
```

Finally, to register a function you need to call #loadFunction(InterpreterFunction $function) method from FieldInterpreter BEFORE call #run method

```php

class AvgFunction extends AggregateFunction {

    public function __construct()
    {
        parent::__construct("AVG");
    }

    /**
     * Returns the average value within iterable $data
     * If $data is empty, then return null
     * @return mixed
     */
    protected function avg(){

        if (empty($this->iterable)) return null;

        if (!$this->field)
            return array_sum($this->iterable)/ count($this->iterable);
        else {

            $values = array_map(function($obj){
                return $obj->{$this->field} ?? null;
            }, $this->iterable );

            return array_sum($values) / count($values);

        }
    }

    /**
     * Given an interable, returns the avergage interpreted value
     * @param $args
     * @return mixed
     */
    public function run(...$args)
    {
        parent::run(...$args);
        return $this->avg();
    }
}

$input = "AVG(warrior_list[].kills)";
$lexer = new FieldLexer($input);
$parser = new FieldParser($lexer);
$fi = new FieldInterpreter($parser, $data);
$fi
    ->loadFunction(new AvgFunction());

$res = $fi->run();

var_dump($res);

/**
 * array(1) {
 * ["AVG(warrior_list[].kills)"]=>
 * float(48.333333333333)
 *}
 */
```

IMPORTANT: function name MUST BE the same in field language syntax. If you named "AVG" your function, then in $input syntax, the filter should be "AVG" as well.

### ModelMap

As I explained before, the model map is the key of the api data mapper. It handles all the ugly tasks to parse and manage data.

Fortunately, the only thing we have to do is tell to a model map how we need to map the fields and if we need to preprocess and postproces them.

For example, imagine we have these entity classes:

```php

/**
 * An other ORM class or system class that is being used by another class as a property
 */
class Color {

    protected string $name;

    public function __construct(string $color)
    {
        $this->name = $color;
    }

    public static function fromName(string $color): Color {

        return new static($color);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function __toString()
    {
        return '<Color(' .$this->getName(). ')>';
    }
}

/**
 * A potential ORM entity.
 */
class Monster {

    protected string $name;

    protected Color $color;

    protected bool $eatHumans;

    protected int $numLegs;

    public function getName(): string
    {
        return $this->name;
    }

    public function getColor(): Color
    {
        return $this->color;
    }

    public function eatsHumans(): bool
    {
        return $this->eatHumans;
    }

    public function getNumLegs(): int
    {
        return $this->numLegs;
    }
}

```

And imagine we have this source of data:
```php
 // https://a-monster-api.com/api/monster/Blob
$jsonIncomingFromMonsterAPI = <<<JSON
{
    "monster": {
        "name": "Blob",
        "eat_humans": 0,
        "color": "green",
        "num_legs": 0
    }
}
JSON;
```

How we should map that data into our Monster entity?

Without a data mapper, we will probably design an specific DTO or similar. Maybe some people hardcode transformation ( yeah I see a lot of things in this live), whatever.

But what if I told you that only you need is a mapping configuration (and maybe a model map factory)?

```php
use didix16\Api\ApiDataMapper\ModelMapFactoryInterface;
use didix16\Api\ApiDataMapper\ModelMapInterface;

class ModelMapFactory implements ModelMapFactoryInterface
{
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
```

```php

use didix16\Api\ApiDataMapper\ModelMap;

class MonsterModelMap extends ModelMap
{
    public function __construct()
    {
        parent::__construct();
        $this
            // configured single fields
            ->mapFields([
                'monster.name'              => 'name',
                'monster.color'             => 'color:getColor',
                'monster.eat_humans:boolean'=> 'eatHumans',
                'monster.num_legs'          => 'numLegs'
            ]);
    }
}
```

```php
$apiData = MonsterApiDataObject::fromJson($jsonIncomingFromMonsterAPI);

/**
 * @var Monster $monster
 */
$monster = $apiDataMapper
    ->configure(Monster::class)
    ->use([
        new GetColorMapFunction()
    ])
    ->mapper
    ->mapToModel(Monster::class, $apiData);

echo "\n";
echo "\n";
echo 'Name: ' . $monster->getName() . "\n";
echo 'Eat Humans: ' . ($monster->eatsHumans() ? 'yes' : 'no') . "\n";
echo 'Color: ' . $monster->getColor() . "\n";
echo 'Number of legs: ' . $monster->getNumLegs() . "\n";
echo '========================'. "\n";
```

WOW, that's a lot information! Yeah I know. But for the moment pay attention to MonsterModelMap and look mapFields method. It sounds familiar to you, right? Correct, the field language! As we can see, a model map uses the field interpreter to map the fields for us but also is able to execute a "post-parsing" function before set the final value to our entity. What does that means? Well, look at the color property of our monster.

 It is not just an "ordinary" value like a string or number, is a class! Well it is true that we could made some filter that transforms a value into a class but, filters only have access to a single value. What happens if we need more data? Well the answer is the [ModelMapFunction](src/ModelMapFunction.php). This kind of function allows to us make last data transformations before set to our entity and also has access to the APIDataObject.

 Remember the color property? Yeah, the color is a class, so, we can add a model map function that resolves a value and turns into our Color class. If we have complex classes that require more data that comes from API, we can access that data but also we can call any service from our app, like DDBB storage or something and do the things we have to do with it :).

#### ModelMapFunction

Model map functions are very easy to implement: just need the run(...$args) method.

$args 0 contains the value coming from interpreter (after filter pipeline)

$args 1 holds the entire APIDataObject.

NOTE: Data may not be the original one because the Interpreter may changed the value by applying your filters.

```php
use didix16\Api\ApiDataMapper\ModelMapFunction;

class GetColorMapFunction extends ModelMapFunction
{
    // parameters and its default values
    protected $parameterName = null;

    public function run(...$args)
    {
        $colorName = $args[0];
        $apiDataObject = $args[1];
        $fieldName = $args[2];

        /**
         * At v1.0.5+ also you can pass external parameters to be used inside run method at construction time
         * 
         * Remember that the given parameters should exists in your ModelMapFunction
         * 
         * You can build ModelMapFunction using:
         * 
         * new YourModelMapFunction("", ['param1' => 'value1', ...])
         * YourModelMapFunction::withParameters(['param1' => 'value1', ...]) <== this is an alias of constructor above
         * 
         * Example:
         * 
         * GetColorMapFunction::withParameters(['parameterName' => '#FF0000'])
         * 
         */
        $colorString = $this->parameterName; // #FF0000

        return new Color($colorName);
    }
}
``` 

Finally how we tell to our model map that use this function?

Well that is simple. Remember this line?

```php
'monster.color' => 'color:getColor',
```

Perfect! As you deducted, the key of the mapFields array is the input of the field language and the value must be the field of our entity but optinally it can be suffixed by a colon and a function name. Usually, the name of the function is the camel case of the name class without the MapFunction suffix, thus is why the class names of every MapFunction should be:

```
<YourFunctionName>MapFunction
```

But that's not all, we have to register the function to our
model map.

Well there is a #use() method that allows us to register not one but three kind of objects:

- ModelMapFunction
- AggregateFunction
- FieldFilter

This way we can extend our language field mapping though a model map instead of talking directly with the fieldparser.

#### ModelMapFactory

Why we need a factory to instnatiate model maps? Well this is a pice of the amazing ApiDataMapper that allows to pass a class of any kind ( the ones we have in our ModelMapFactory) and let configure our model maps in runtime as well as leave the tough work to ApiDataMapper.

Remember that our goal is only to configure the mapping and leave the rest to APIDataMapper :)


As we saw before, this is an example of a factory that can generate mappings for Warrior and Monster classes.

**In the future I'll change this because no one wants to have a gigantic switch for each class :)**

In the mean time, you can generate different model maps factories and have different api data mappers.

```php
use didix16\Api\ApiDataMapper\ModelMapFactoryInterface;
use didix16\Api\ApiDataMapper\ModelMapInterface;

class ModelMapFactory implements ModelMapFactoryInterface
{
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
```

### ApiDataObject

- See [didix16/php-apidataobject][1] - A simple library that allows easy handle incoming data from any sources (specially from API sources)

# ApiDataMapper

And here we are, the API functionality usage of this marvelous package :)

Basically, from this packge we will need:
    
- GlobalApiDataMapper (or ApiDataMapper if you need something else)
    -   ```php 
        /**
         * Given A model class and an ApiDataObject, attempt to generate an instance of $modelClass with data given
        * @param $modelClass - Should be any kind of ORM entity or object class representing a model in DDBB
        * @param ApiDataObjectInterface $data
        * @return object
        * @throws ApiDataMapperException
        */
        public function mapToModel($modelClass, ApiDataObjectInterface $data): object
        ```
    -   ```php 
        /**
         * Given a model class and ApiDataObjectInterface, attempts to generate an interable of
        * $modelClass with data given
        * @param $modelClass - Should be any kind of ORM entity or object class representing a model in DDBB
        * @param ApiDataObjectInterface $data
        * @return iterable
        * @throws ApiDataMapperException
        */
        public function mapToModelList($modelClass, ApiDataObjectInterface $data): iterable
        ```
    -   ```php 
        /**
         * Given an instance of a model and an ApiDataObjectInterface, attempt to refresh the model with data given
        * @param object $instance
        * @param ApiDataObjectInterface $data
        * @throws ApiDataMapperException
        */
        public function refreshModel(object $instance, ApiDataObjectInterface $data): void
        ```  
    -   ```php
        /**
         * Refreshes instance $to using instance $from
        * If $strict is true and the instances are not the same class then an exception is thrown
        * @param object $to,
        * @param object $from
        * @param bool $strict
        * @throws ApiDataMapperException
        */
        public function refreshModelFromOtherModel(object $to, object $from, bool $strict = false): void
        ```

        
- ModelMap

    -   ```php
        /**
         * Tell to this model map that should generate multiple instances by using $arrayField as field list
        * @param string $arrayField
        * @return ModelMap
        */
        public function setMultiple(string $arrayField): self
        ```
    -   ```php
        /**
         * Tell to this model map that don't generate multiple instances.
        * This methods unset the arrayField if was stablished using #setMultiple method
        */
        public function unsetMultiple(): self
        ```
    -   ```php
        /**
         * Check if this model map is configured to process and return a multiple instances
        */
        public function isMultiple(): bool
        ```
    -   ```php
        /**
         * Given an associative array with key as externalField and a value as modelField,
        * tries to make the association for this model map
        * @param array $fieldMap
        * @return $this
        * @throws Exception
        */
        public function mapFields(array $fieldMap): self
        ```
    -   ```php
        /**
         * Same as mapFields but for list fields
        */
        public function mapListFields(array $listFieldMap): self
        ```
    -   ```php
        /**
         * Allows to register external components to extends the functionality of the model map language
        * The components allowed are: FieldFilter, AggregateFunction and ModelMapFunction
        * @param FieldFilter|FieldFilter[]|AggregateFunction|AggregateFunction[]|ModelMapFunction|ModelMapFunction[] $components
        */
        public function use($components): self
        ```
    -   ```php
        /**
         * 
         * ACCESSIBLE ONLY FROM GlobalApiDataMapper. It uses HasModelMapFactory Trait.
         * 
         * Allows to access the model map for specified $modelClass
        * Returns a HighOrderModelMapConfiguration to allow chain access
        * between model map and api data mapper
        * @param string $modelClass
        * @return HighOrderModelMapConfiguration
        */
        public function configure($modelClass): HighOrderModelMapConfiguration
        ```
        
    Basically you will make classes from this class by each of your entities.

    For example, supose we have a Warrior entity. This could be a model map configuration

    ```php
    use didix16\Api\ApiDataMapper\ModelMap;

    class WarriorModelMap extends ModelMap
    {
        public function __construct()
        {
            parent::__construct();
            $this
                // configured single fields
                ->mapFields([
                    'warrior.name'              => 'name',
                    'warrior.is_active:boolean' => 'active',
                    'warrior.weapon'            => 'weapon',
                    'warrior.comes_from'        => 'place'
                ])
                // configuring map list fields when coming from a list but not using #setMultiple() method here
                ->mapListFields([
                    'name'              => 'name',
                    'is_active:boolean' => 'active',
                    'weapon'            => 'weapon'
                ]);
        }
    }
    ```
- ModelMapFactory
    -   ```php
        /**
         * Given a $modelClass, returns a new instance of a ModelMapInterface
        * that is related to the $modelClass
        */
        public static function build($modelClass): ModelMapInterface
        ```
- FieldFilter
    -   ```php

        # EXTENDS THIS CLASS

        /**
         * The name this filter has
         * Must be the same on syntax field language
         */
        public function __construct($name);
        ```
    -   ```php
        /**
         * Gets a $value and transform it into something else
         */
        protected function transform(&$value);
        ```
- AggregateFunction
    -   ```php

        # EXTENDS THIS CLASS

        /**
         * The list you want to iterate
         */
        protected $iterable = [];
        /**
         * The field name from every object inside the list. If null means we only
         * iterate over list elements,
         * else each elem shuld be an object
         */
        protected $field = null;
        
        /**
         * Do this allways!
         */
        protected function run(...$args){

            parent::run($args);
            // do whatever you want from here
        }
        ```
- ApiDataObject

    Better explained with examples:

    ```php

    class ApiPlatformDataObject extends ApiDataObject {}

    $json = <<<JSON
    {
        "property1": "value1",
        "property2": "value2",
        ...
    }
    JSON;

    $apiData = ApiPlatformDataObject::fromJson($json);

        /**
         * Different accessors
         */
        $apiData['property1'];
        $apiData->property1;
        $apiData->property1();

        /**
         * Different setters
         */
        $apiData['property1'] = 'value5';
        $apiData->property1 = 'value5';

        // chainable setter properties
        $apiData
        ->property1('value5')
        ->property2('value6')
        ...
    ```

    ```php
    $data = [
        'property1' => 'value1',
        'property2' => 'value2',
        ...
    ];

    /**
     * Instantiate from an array
     */
    $apiData = new ApiPlatformDataObject($data);

    $data = (object)[
        'property1' => (object)[
            'objProp1' => 1,
            'objProp2' => 2,
            ...
        ],
        'property2' => 'value2',
        ...
    ];

    /**
     * Instantiate from an object
     */
    $apiData = new ApiPlatformDataObject($data);
    ```

    Feel free to instnatiate your api data object from any source and remember transform it to a valid php data.

    For example you could read from XML and transform it to an array or an object. This could be done inside static function called fromXML():

    ```php

    class MyXMLApiDataObject extends ApiDataObject {

        public static fromXML($xml): MyXMLApiDataObject
        {
            // parse your XML
            // ... or whatever ...
            $list = $xmlParsed;
            return new static($list);
        }
    }
    ```
    You know what I mean, let fly your imagination ;)


## Examples

You will find some examples at [examples folder](examples)

You sould look at:

- index.php
- FieldInterpreter.php

They are ready to go, so if you open your terminal after installation:

```sh
php vendor/didix16/php-apidatamapper/examples/index.php
```
OR

```sh
php vendor/didix16/php-apidatamapper/examples/FieldInterpreter.php
```

You should see a few results from the examples

# Credits

Feel free to use this library but do not forget to mention that I'm the owner :).

Sorry for my bad english. I'll try to fix any grammar errors in the future.

Also feel free to send me an issue, report bugs, suggestions, etc.

[1]:https://github.com/didix16/php-apidataobject