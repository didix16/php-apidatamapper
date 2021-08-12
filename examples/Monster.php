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