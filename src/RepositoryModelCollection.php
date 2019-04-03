<?php

namespace Prophets\DrupalJsonApi;

use Illuminate\Support\Collection as BaseCollection;
use Prophets\DrupalJsonApi\Contracts\RepositoryModels;

class RepositoryModelCollection extends BaseCollection implements RepositoryModels
{

    /**
     * RepositoryModelCollection constructor.
     */
    public function __construct()
    {
        $this->items = config('drupal-jsonapi.repository_models', []);
    }

    /**
     * Get a model class by it's resource name.
     *
     * @param string $resourceName
     *
     * @return string|null
     */
    public function findByResourceName($resourceName)
    {
        return $this->first(function ($class) use ($resourceName) {
            return $class::getResourceName() === $resourceName;
        });
    }
}
