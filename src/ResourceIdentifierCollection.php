<?php

namespace Prophets\DrupalJsonApi;

use Illuminate\Support\Collection;
use Prophets\DrupalJsonApi\Contracts\ResourceIdentifierObject;

class ResourceIdentifierCollection extends Collection
{
    /**
     * @param $type
     * @param $id
     *
     * return ResourceIdentifierObject|null
     */
    public function find($type, $id)
    {
        return $this->first(function (ResourceIdentifierObject $resourceIdentifierObject) use ($type, $id) {
            return $resourceIdentifierObject->type() === $type && $resourceIdentifierObject->id() === $id;
        });
    }
}
