<?php

require_once __DIR__ .'/../vendor/autoload.php';

use didix16\examples\Warrior;
use didix16\Api\ApiDataMapper\GlobalApiDataMapper;
use didix16\examples\GetColorMapFunction;
use didix16\examples\ModelMapFactory;
use didix16\examples\Monster;
use didix16\examples\MonsterApiDataObject;
use didix16\examples\WarriorApiDataObject;
use didix16\Hydrator\ReflectionHydrator;

$modelMapFactory = new ModelMapFactory();
$classHydrator = new ReflectionHydrator();
$apiDataMapper = new GlobalApiDataMapper($modelMapFactory, $classHydrator);

/*********************************************
 * A single mapping
 *********************************************/

 // https://a-warrior-api.com/api/warrior/Lancelot
$jsonIncomingFromWarriorAPI = <<<JSON
{
    "warrior": {
        "name": "Lancelot",
        "is_active": "no",
        "weapon": "Spear",
        "comes_from": "Camelot"
    }
}
JSON;

$apiData = WarriorApiDataObject::fromJson($jsonIncomingFromWarriorAPI);

$aWarrior = $apiDataMapper->mapToModel(Warrior::class, $apiData);

// Lancelot info
echo 'Name: ' . $aWarrior->getName() . "\n";
echo 'Is Active: ' . ($aWarrior->isActive() ? 'yes' : 'no') . "\n";
echo 'Holding weapon: ' . $aWarrior->getWeapon() . "\n";
echo 'Comes from: ' . $aWarrior->getPlace() . "\n";


/*********************************************
 * Multiple mapping
 *********************************************/

 // https://a-warrior-api.com/api/from/Camelot
$jsonIncomingFromWarriorAPI = <<<JSON
{
    "comes_from": "Camelot",
    "warrior_list": [
        {
            "name": "Lancelot",
            "is_active": 0,
            "weapon": "Spear"
        },
        {
            "name": "Arthur",
            "is_active": 1,
            "weapon": "Sword"
        },
        {
            "name": "Merlin",
            "is_active": 0,
            "weapon": "Catalyst"
        }
    ]
    
}
JSON;

$apiData = WarriorApiDataObject::fromJson($jsonIncomingFromWarriorAPI);


// HighOrderProxy to configure the ModelMap
$warriorList = $apiDataMapper
    ->configure(Warrior::class)
    // checkout WarriorModelMap to figure out how list fields are configured using #mapListFields method
    ->setMultiple('warrior_list[]')
    // Set this field to all warriors
    ->mapFields([
        "comes_from" => 'place'
    ])
    ->mapper
    ->mapToModelList(Warrior::class, $apiData);

// Warrior list info

echo "\n";
echo "\n";
echo "=======================\n";
echo "     WARRIOR LIST      \n";
echo "=======================\n";
echo "\n";

/**
 * @var Warrior[] $warriorList
 */
foreach($warriorList as $warrior){

    echo 'Name: ' . $warrior->getName() . "\n";
    echo 'Is Active: ' . ($warrior->isActive() ? 'yes' : 'no') . "\n";
    echo 'Holding weapon: ' . $warrior->getWeapon() . "\n";
    echo 'Comes from: ' . $warrior->getPlace() . "\n";
    echo '========================'. "\n";
}


/*********************************************
 *  Fill model from other model
 *********************************************/
echo "\n";
echo "\n";

$warrior = new Warrior();
$warrior
    ->setName('Lancelot')
    ->setIsActive(true)
    ->setWeapon('Spear')
    ->setPlace('Camelot');

$lancelot = new Warrior();
$apiDataMapper->refreshModelFromOtherModel($lancelot, $warrior);

echo 'Name: ' . $warrior->getName() .                        '       | Name(clone): ' . $lancelot->getName() . "\n";
echo 'Is Active: ' . ($warrior->isActive() ? 'yes' : 'no') . '       | Is Active(clone): ' .($lancelot->isActive() ? 'yes' : 'no') ."\n";
echo 'Holding weapon: ' . $warrior->getWeapon() .            '| Holding weapon(clone): ' . $lancelot->getWeapon() . "\n";
echo 'Comes from: ' . $warrior->getPlace() .                 '  | Comes from(clone): ' . $lancelot->getPlace() . "\n";

/*********************************************
 *  Refresh existing model with new data
 *********************************************/

// https://a-warrior-api.com/api/warrior/Lancelot
$jsonIncomingFromWarriorAPI = <<<JSON
{
    "warrior": {
        "name": "Lancelot of the Lake",
        "is_active": 0,
        "weapon": "Legendary Spear",
        "comes_from": "Camelot"
    }
}
JSON;

$apiData = WarriorApiDataObject::fromJson($jsonIncomingFromWarriorAPI);

$apiDataMapper
    ->configure(Warrior::class)
    ->unsetMultiple()
    ->mapFields([
        'warrior.name'              => 'name',
        'warrior.is_active:boolean' => 'active',
        'warrior.weapon'            => 'weapon',
        'warrior.comes_from'        => 'place'
    ])
    ->mapper
    ->refreshModel($warrior, $apiData);

echo "\n";
echo "\n";
echo 'Name: ' . $warrior->getName() . "\n";
echo 'Is Active: ' . ($warrior->isActive() ? 'yes' : 'no') . "\n";
echo 'Holding weapon: ' . $warrior->getWeapon() . "\n";
echo 'Comes from: ' . $warrior->getPlace() . "\n";
echo '========================'. "\n";

/*********************************************
 *  Configure another model and fill it
 *********************************************/

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