<?php

namespace Prophets\DrupalJsonApi\Relations\Concerns;

trait RelationCollectionResult
{
    /**
     * @return \Prophets\DrupalJsonApi\Collection
     */
    public function getResults()
    {
        return $this->getRepository()->getForRelation($this);
    }
}
