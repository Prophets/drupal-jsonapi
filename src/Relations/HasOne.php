<?php

namespace Prophets\DrupalJsonApi\Relations;

class HasOne extends HasOneManySingle
{
    /**
     * @return \Prophets\DrupalJsonApi\Model|null
     */
    public function getResults()
    {
        return $this->getRepository()->findForRelation($this);
    }
}
