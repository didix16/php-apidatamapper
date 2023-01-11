<?php

namespace didix16\examples;

/**
 * A potential ORM entity.
 */
class Monster {

    protected string $name;

    protected Color $color;

    protected bool $eatHumans;

    protected int $numLegs;

    public function setName($name): Monster
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setColor($color): Monster
    {
        $this->color = $color;
        return $this;
    }

    public function getColor(): Color
    {
        return $this->color;
    }

    public function setEatHumans($flag): Monster
    {
        $this->eatHumans = $flag;
        return $this;
    }

    public function eatsHumans(): bool
    {
        return $this->eatHumans;
    }

    public function setNumLegs($legs): Monster
    {
        $this->numLegs = $legs;
        return $this;
    }

    public function getNumLegs(): int
    {
        return $this->numLegs;
    }
}