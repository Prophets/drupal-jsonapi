<?php

namespace Prophets\DrupalJsonApi;

use Illuminate\Support\Collection as BaseCollection;

class Collection extends BaseCollection
{
    /**
     * Get a model by it's resource name and id.
     *
     * @param string $resourceName
     * @param string $id
     *
     * @return Model|null
     */
    public function find($resourceName, $id)
    {
        return $this->first(function (Model $model) use ($resourceName, $id) {
            return $model->getResourceName() === $resourceName && $model->getId() === $id;
        });
    }
}
