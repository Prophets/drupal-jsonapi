<?php

namespace Prophets\DrupalJsonApi\Relations;

use Prophets\DrupalJsonApi\Contracts\BaseRelationHasMany;
use Prophets\DrupalJsonApi\Contracts\BaseRelationMixed;

class HasManyMixed extends Relation implements BaseRelationMixed, BaseRelationHasMany
{
    use Concerns\RelationCollectionResult;

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
