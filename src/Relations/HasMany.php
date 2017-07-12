<?php

namespace Prophets\DrupalJsonApi\Relations;

use Prophets\DrupalJsonApi\Contracts\BaseRelationHasMany;

class HasMany extends HasOneManySingle implements BaseRelationHasMany
{
    use Concerns\RelationCollectionResult;
}
