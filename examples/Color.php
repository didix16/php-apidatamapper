<?php

namespace didix16\examples;

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