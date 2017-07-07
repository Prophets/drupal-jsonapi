<?php

namespace Prophets\DrupalJsonApi\Contracts;

use Prophets\DrupalJsonApi\Model;

interface BaseRelationSingle
{
    /**
     * @return Model
     */
    public function getNewModel(): Model;
}
