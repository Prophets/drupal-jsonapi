<?php

namespace Prophets\DrupalJsonApi\Contracts;

interface BaseRelationMixed
{
    /**
     * @param array $classList
     */
    public function setClassList(array $classList);

    /**
     * @return array
     */
    public function getClassList(): array;
}
