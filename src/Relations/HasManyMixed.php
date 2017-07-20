<?php

namespace Prophets\DrupalJsonApi\Relations;

use Prophets\DrupalJsonApi\Contracts\BaseRelationHasMany;
use Prophets\DrupalJsonApi\Contracts\BaseRelationMixed;

class HasManyMixed extends Relation implements BaseRelationMixed, BaseRelationHasMany
{
    use Concerns\RelationCollectionResult;
    use Concerns\RelationClassList;
}
