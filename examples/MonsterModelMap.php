<?php

namespace didix16\examples;

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