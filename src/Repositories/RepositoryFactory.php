<?php

namespace Prophets\DrupalJsonApi\Repositories;

use Prophets\DrupalJsonApi\Contracts\BaseRepository;
use Prophets\DrupalJsonApi\Contracts\RepositoryFactory as BaseRepositoryFactory;

class RepositoryFactory implements BaseRepositoryFactory
{
    /**
     * @var string
     */
    protected $defaultRepositoryNamespace;

    /**
     * RepositoryFactory constructor.
     *
     * @param string $defaultRepositoryNamespace
     */
    public function __construct($defaultRepositoryNamespace = 'App')
    {
        $this->defaultRepositoryNamespace = $defaultRepositoryNamespace;
    }

    /**
     * Create a repository based upon $model class name.
     *
     * @param $model
     * @param null $repositoryClassName
     * @param array $params
     *      'cacheDecorator'
     *      'cache'
     * @return BaseRepository
     */
    public function create($model, $repositoryClassName = null, array $params = [])
    {
        if ($repositoryClassName === null) {
            $repositoryBaseName = $this->getModelName($model);
            $repositoryClassName = $this->getRepositoryClassName($repositoryBaseName);
        }
        $modelObject = $model;
        $cacheDecorator = null;

        if (is_string($modelObject)) {
            $modelObject = new $modelObject;
        }
        $cacheDecorator = $params['cacheDecorator'] ?? null;
        $caching = $params['caching'] ?? $cacheDecorator !== false;
        unset($params['cacheDecorator']);
        unset($params['cache']);

        $repository = new $repositoryClassName($modelObject, $params);

        if (! $repository instanceof BaseRepository) {
            throw new \InvalidArgumentException('Repository must implement BaseRepository.');
        }
        if ($caching === false) {
            return $repository;
        }
        if ($cacheDecorator === null) {
            $cacheDecoratorClass = $this->getCacheDecoratorClassName($repositoryClassName, $this->getModelName($model));
            $cacheDecorator = new $cacheDecoratorClass($repository);
        }
        if (! $cacheDecorator instanceof BaseRepository) {
            throw new \InvalidArgumentException('Cache Decorator must implement BaseRepository.');
        }

        return $cacheDecorator;
    }

    /**
     * Get model name without namespace
     * @param $model
     * @return string
     */
    public function getModelName($model)
    {
        $modelName = is_object($model) ? get_class($model) : $model;

        if (($pos = strrpos($modelName, '\\'))) {
            $modelName = substr($modelName, $pos + 1);
        }
        return $modelName;
    }

    /**
     * Generate the repository class name for a certain $type and $model
     *
     * @param $modelName
     * @return string
     */
    public function getRepositoryClassName($modelName)
    {
        return $this->getRepositoryClassNamespace($modelName) . 'JsonApi' . $modelName . 'Repository';
    }

    /**
     * Generate the cache decorator class name for a certain $model
     *
     * @param string $repositoryClassName
     * @param string $modelName
     * @return string
     */
    public function getCacheDecoratorClassName($repositoryClassName, $modelName)
    {
        $namespace = '';

        if (($pos = strrpos($repositoryClassName, '\\'))) {
            $namespace = substr($repositoryClassName, 0, $pos + 1);
        }
        return $namespace . 'JsonApi' . $modelName . 'CacheDecorator';
    }

    /**
     * Generate the namespace for a certain $model
     *
     * @param $modelName
     * @return string
     */
    public function getRepositoryClassNamespace($modelName)
    {
        return $this->defaultRepositoryNamespace . '\\' . $modelName . '\\';
    }
}
