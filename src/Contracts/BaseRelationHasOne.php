<?php

namespace Prophets\DrupalJsonApi\Contracts;

interface BaseRelationHasOne
{
    /**
     * @return \Prophets\DrupalJsonApi\Model|null
     */
    public function getResults();
}
