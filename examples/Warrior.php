<?php

namespace didix16\examples;

/**
 * A potential ORM entity.
 */
class Warrior {

    protected string $name;
    protected bool   $active;
    protected string $weapon;
    protected string $place;

    public function setName(string $name): self {

        $this->name = $name;
        return $this;
    }
    
    public function getName(): string
    {
        return $this->name;
    }

    public function setIsActive(bool $flag): self
    {
        $this->active = $flag;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setWeapon(string $weapon): self
    {
        $this->weapon = $weapon;
        return $this;
    }

    public function getWeapon(): string
    {
        return $this->weapon;
    }

    public function setPlace(string $place): self
    {
        $this->place = $place;
        return $this;
    }

    public function getPlace(): string
    {
        return $this->place;
    }
}