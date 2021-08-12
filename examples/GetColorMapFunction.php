<?php

namespace didix16\examples;

use didix16\Api\ApiDataMapper\ModelMapFunction;

class GetColorMapFunction extends ModelMapFunction
{
    public function run(...$args)
    {
        $colorName = $args[0];
        $apiDataObject = $args[1];

        return new Color($colorName);
    }
}