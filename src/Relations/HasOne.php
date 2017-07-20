<?php

namespace Prophets\DrupalJsonApi\Relations;

use Prophets\DrupalJsonApi\Contracts\BaseRelationHasOne;

class HasOne extends HasOneManySingle implements BaseRelationHasOne
{
    use Concerns\RelationModelResult;
}
