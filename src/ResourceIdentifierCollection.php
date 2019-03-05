<?php

namespace Prophets\DrupalJsonApi;

use Illuminate\Support\Collection;
use Prophets\DrupalJsonApi\Contracts\ResourceIdentifierObject;

class ResourceIdentifierCollection extends Collection
{
    /**
     * Get a resource identifier by it's type and id.
     *
     * @param string $type
     * @param string $id
     *
     * @return ResourceIdentifierObject|null
     */
    public function find($type, $id)
    {
        return $this->first(function (ResourceIdentifierObject $resourceIdentifierObject) use ($type, $id) {
            return $resourceIdentifierObject->type() === $type && $resourceIdentifierObject->id() === $id;
        });
    }
}
