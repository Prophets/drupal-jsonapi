<?php

namespace Prophets\DrupalJsonApi\Relations\Concerns;

trait RelationModelResult
{
    /**
     * @return \Prophets\DrupalJsonApi\Model|null
     */
    public function getResults()
    {
        return $this->getRepository()->findForRelation($this);
    }
}
