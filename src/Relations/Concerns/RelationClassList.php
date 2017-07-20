<?php

namespace Prophets\DrupalJsonApi\Relations\Concerns;

trait RelationClassList
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
    public function getClassList(): array
    {
        return $this->classList;
    }
}
