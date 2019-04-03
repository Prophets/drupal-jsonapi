<?php

namespace Prophets\DrupalJsonApi\Contracts;

interface RepositoryFactory
{
    /**
     * Create a repository based upon $model class name.
     *
     * @param mixed $model
     * @param null|string $repositoryClassName
     * @param array $params
     *
     * @return BaseRepository
     */
    public function create($model, $repositoryClassName = null, array $params = []);
}
