<?php

namespace Prophets\DrupalJsonApi\Relations;

class HasOne extends Relation
{
    /**
     * @return \Prophets\DrupalJsonApi\Model|null
     */
    public function getResults()
    {
        return $this->getRepository()->findForRelation($this);
    }
}
