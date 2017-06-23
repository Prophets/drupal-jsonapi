<?php

namespace Prophets\DrupalJsonApi\Relations;

class HasMany extends Relation
{
    /**
     * @return \Prophets\DrupalJsonApi\Collection
     */
    public function getResults()
    {
        return $this->getRepository()->getForRelation($this);
    }
}
