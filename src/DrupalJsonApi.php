<?php

namespace Prophets\DrupalJsonApi;

use Prophets\DrupalJsonApi\Contracts\RepositoryFactory;
use Prophets\DrupalJsonApi\Contracts\RepositoryModels;

class DrupalJsonApi
{
    /**
     * @var RepositoryFactory
     */
    protected $repositoryFactory;

    /**
     * @var RepositoryModels
     */
    protected $repositoryModels;

    /**
     * @var bool
     */
    protected $isBooted = false;

    /**
     * DrupalJsonApi constructor.
     *
     * @param RepositoryFactory $repositoryFactory
     * @param RepositoryModels $repositoryModels
     */
    public function __construct(RepositoryFactory $repositoryFactory, RepositoryModels $repositoryModels)
    {
        $this->repositoryFactory = $repositoryFactory;
        $this->repositoryModels = $repositoryModels;
    }

    /**
     * Boot registrations.
     */
    public function boot()
    {
        if ($this->isBooted === true) {
            return;
        }
        $this->laravelRegisterRepositories();
        $this->isBooted = true;
    }

    /**
     * Register repositories in the Laravel application container.
     */
    protected function laravelRegisterRepositories()
    {
        /**
         * Illuminate\Container\Container
         */
        $container = app();
        $params = [
          'caching' => config('drupal-jsonapi.use_cache')
        ];

        foreach ($this->repositoryModels as $modelClass) {
            $repositoryClassName = $modelClass::getRepositoryClassName();
            $repositoryInterface = $modelClass::getRepositoryInterface();

            $container->bind(
                $repositoryInterface,
                function ($app) use ($repositoryClassName, $modelClass, $params) {
                    return $this->repositoryFactory->create(
                        $modelClass,
                        $repositoryClassName,
                        $params
                    );
                }
            );
        }
    }

    /**
     * @return RepositoryModels
     */
    public function getRepositoryModels(): RepositoryModels
    {
        return $this->repositoryModels;
    }
}
