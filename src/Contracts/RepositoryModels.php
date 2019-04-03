<?php

namespace Prophets\DrupalJsonApi\Contracts;

interface RepositoryModels
{
    /**
     * Get a model class by it's resource name.
     *
     * @param string $resourceName
     *
     * @return string|null
     */
    public function findByResourceName($resourceName);
}
