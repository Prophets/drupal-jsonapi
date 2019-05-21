<?php

namespace Prophets\DrupalJsonApi\Repositories;

use Illuminate\Cache\TaggableStore;
use Prophets\DrupalJsonApi\Contracts\BaseRelation;
use Prophets\DrupalJsonApi\Contracts\BaseRepository;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Config\Repository as ConfigRepository;

class JsonApiBaseCacheDecorator implements BaseRepository
{
    /**
     * @var BaseRepository
     */
    protected $repository;

    /**
     * @var \Illuminate\Cache\Repository
     */
    protected $cache;

    /**
     * @var string The entity name
     */
    protected $entityName;

    /**
     * @var int caching time
     */
    protected $cacheTime;

    /**
     * @var mixed|\Prophets\DrupalJsonApi\Model
     */
    protected $model;

    /**
     * CacheBaseDecorator constructor.
     * @param JsonApiBaseRepository $repository
     */
    public function __construct(JsonApiBaseRepository $repository)
    {
        $this->cache = app(CacheRepository::class);
        $this->cacheTime = app(ConfigRepository::class)->get('cache.time', 60);
        $this->repository = $repository;
        $this->model = $repository->getNewModel();
        $this->entityName = call_user_func([$this->model, 'getResourceName']);
    }

    /**
     * @inheritdoc
     */
    public function getNewModel()
    {
        return $this->repository->getNewModel();
    }

    /**
     * @inheritdoc
     */
    public function find($id)
    {
        return $this->getCacheStore()
            ->remember(
                $this->getCacheIdentifier(),
                $this->cacheTime,
                function () use ($id) {
                    return $this->proxyToRepository('find', [$id]);
                }
            );
    }

    /**
     * @inheritdoc
     */
    public function findByAttributes(array $attributes)
    {
        // sort attributes to improve cache hit
        ksort($attributes);

        return $this->getCacheStore()
            ->remember(
                $this->getCacheIdentifier(),
                $this->cacheTime,
                function () use ($attributes) {
                    return $this->proxyToRepository(
                        'findByAttributes',
                        [$attributes]
                    );
                }
            );
    }

    /**
     * @inheritdoc
     */
    public function findForRelation(BaseRelation $relation)
    {
        return $this->getCacheStore()
            ->remember(
                $this->getCacheIdentifier(),
                $this->cacheTime,
                function () use ($relation) {
                    return $this->proxyToRepository(
                        'findForRelation',
                        [$relation]
                    );
                }
            );
    }

    /**
     * @inheritdoc
     */
    public function getByAttributes(
        array $attributes,
        $orderBy = null,
        $page = null
    ) {
        // sort attributes to improve cache hit
        ksort($attributes);

        return $this->getCacheStore()
            ->remember(
                $this->getCacheIdentifier(),
                $this->cacheTime,
                function () use ($attributes, $orderBy, $page) {
                    return $this->proxyToRepository(
                        'getByAttributes',
                        [$attributes, $orderBy, $page]
                    );
                }
            );
    }

    /**
     * @inheritdoc
     */
    public function getByIds(array $ids)
    {
        // sort attributes to improve cache hit
        sort($ids);

        return $this->getCacheStore()
            ->remember(
                $this->getCacheIdentifier(),
                $this->cacheTime,
                function () use ($ids) {
                    return $this->proxyToRepository(
                        'getByIds',
                        [$ids]
                    );
                }
            );
    }

    /**
     * @inheritdoc
     */
    public function getForRelation(BaseRelation $relation)
    {
         return $this->getCacheStore()
            ->remember(
                $this->getCacheIdentifier(),
                $this->cacheTime,
                function () use ($relation) {
                    return $this->proxyToRepository(
                        'getForRelation',
                        [$relation]
                    );
                }
            );
    }

    /**
     * Proxy repository methods to 'backend'
     * @param $method
     * @param array $arguments
     * @return mixed
     */
    protected function proxyToRepository($method, array $arguments = [])
    {
        return call_user_func_array([
            $this->repository,
            $method
        ], $arguments);
    }

    /**
     * Format a sensible cache identifier, prevent the key of being too long and use an md5 hash for arguments
     * @param $name
     * @param $arguments
     * @return string
     */
    protected function formatCacheIdentifier($name, $arguments)
    {
        $identifier = '';

        if (is_array($arguments) && count($arguments)) {
            $identifier .= serialize($arguments);
        }
        $scopes = $this->repository->getGlobalScopes();

        if (count($scopes)) {
            $identifier .= serialize($scopes);
        }
        if ($this->repository->isAuthEnabled()) {
            $identifier .= 'authEnabled';
        }
        if ($this->repository->isWithoutIncludes()) {
            $identifier .= 'withoutIncludes';
        }

        return "{$this->entityName}.{$name}." . sha1($identifier);
    }

    /**
     * @return string
     */
    protected function getCacheIdentifier()
    {
        $caller = debug_backtrace(null, 2)[1];

        return $this->formatCacheIdentifier(
            $caller['function'],
            $caller['args']
        );
    }

    /**
     * Get cache repository, if tags supported, intialized with $tags and default tags.
     *
     * @param array $tags
     * @return CacheRepository|\Illuminate\Cache\TaggedCache
     */
    protected function getCacheStore(array $tags = [])
    {
        if ($this->cache instanceof TaggableStore) {
            return $this->cache
                ->tags(array_merge([$this->entityName, 'jsonapi'], $tags));
        }
        return $this->cache;
    }

    /**
     * Clear the cache for this repository's entity
     * @return bool
     */
    public function clearCache()
    {
        return $this->cache->tags($this->entityName)->flush();
    }
}
