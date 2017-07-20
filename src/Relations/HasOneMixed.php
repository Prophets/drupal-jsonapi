<?php

namespace Prophets\DrupalJsonApi\Relations;

use Prophets\DrupalJsonApi\Contracts\BaseRelationHasOne;
use Prophets\DrupalJsonApi\Contracts\BaseRelationMixed;

class HasOneMixed extends Relation implements BaseRelationMixed, BaseRelationHasOne
{
    use Concerns\RelationModelResult;
    use Concerns\RelationClassList;
}
