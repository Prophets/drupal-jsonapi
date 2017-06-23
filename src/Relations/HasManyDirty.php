<?php

namespace Prophets\DrupalJsonApi\Relations;

class HasManyDirty extends HasMany
{
    /**
     * @var array
     */
    protected $classList = [];

    /**
     * @param array $classList
     */
    public function setClassList(array $classList)
    {
        $this->classList = $classList;
    }

    /**
     * @return array
     */
    public function getClassList()
    {
        return $this->classList;
    }
}
