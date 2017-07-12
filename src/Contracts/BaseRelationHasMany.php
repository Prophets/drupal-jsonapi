<?php

namespace Prophets\DrupalJsonApi\Contracts;

interface BaseRelationHasMany
{
    /**
     * @return \Prophets\DrupalJsonApi\Collection
     */
    public function getResults();
}
