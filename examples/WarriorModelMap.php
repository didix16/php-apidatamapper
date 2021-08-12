<?php

namespace didix16\examples;

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