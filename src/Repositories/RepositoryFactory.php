<?php

namespace Prophets\DrupalJsonApi\Repositories;

use Prophets\DrupalJsonApi\Contracts\BaseRepository;

class RepositoryFactory
{
    /**
     * Use cache decorators flag.
     *
     * @var bool
     */
    protected $useCache;
    /**
     * @var string
     */
    protected $defaultRepositoryNamespace;

    /**
     * RepositoryFactory constructor.
     *
     * @param boolean $useCache
     * @param string $defaultRepositoryNamespace
     */
    public function __construct($useCache, $defaultRepositoryNamespace = 'App')
    {
        $this->useCache = (boolean) $useCache;
        $this->defaultRepositoryNamespace = $defaultRepositoryNamespace;
    }

    /**
     * Create a repository based upon $model class name.
     *
     * @param $model
     * @param null $repositoryClassName
     * @param null|false $cacheDecorator
     * @return mixed
     */
    public function create($model, $repositoryClassName = null, $cacheDecorator = null)
    {
        if ($repositoryClassName === null) {
            $repositoryBaseName = $this->getModelName($model);
            $repositoryClassName = $this->getRepositoryClassName($repositoryBaseName);
        }
        $modelObject = $model;

        if (is_string($modelObject)) {
            $modelObject = new $modelObject;
        }
        $repository = new $repositoryClassName($modelObject);

        if (! $repository instanceof BaseRepository) {
            throw new \InvalidArgumentException('Cache Decorator must implement BaseRepository.');
        }
        if (! $this->useCache || $cacheDecorator === false) {
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
