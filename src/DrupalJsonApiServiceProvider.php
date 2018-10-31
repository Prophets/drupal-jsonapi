<?php

namespace Prophets\DrupalJsonApi;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Illuminate\Support\ServiceProvider;
use Prophets\DrupalJsonApi\Contracts\BaseRepository;
use Prophets\DrupalJsonApi\Repositories\RepositoryFactory;

class DrupalJsonApiServiceProvider extends ServiceProvider
{
    protected $commands = [
        Console\RepositoryMakeCommand::class,
        Console\CacheDecoratorMakeCommand::class,
        Console\InterfaceMakeCommand::class,
    ];

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/drupal-jsonapi.php', 'drupal-jsonapi');

        $this->publishes([
            __DIR__.'/../config/drupal-jsonapi.php' => config_path('drupal-jsonapi.php'),
        ]);
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $repositoryFactory = new RepositoryFactory(config('drupal-jsonapi.use_cache'));
        $this->app->instance(RepositoryFactory::class, $repositoryFactory);
        $repositories = config('drupal-jsonapi.repository_models', []);

        /**
         * Register repositories from config.
         */
        foreach ($repositories as $modelClass) {
            $repositoryClassName = call_user_func($modelClass . '::getRepositoryClassName');
            $repositoryInterface = call_user_func($modelClass . '::getRepositoryInterface');

            $this->app->bind(
                $repositoryInterface,
                function ($app, array $params = []) use ($repositoryFactory, $repositoryClassName, $modelClass) {
                    return $repositoryFactory->create(
                        $modelClass,
                        $repositoryClassName,
                        $params
                    );
                }
            );
        }

        /**
         * Register artisan commands
         */
        $this->commands($this->commands);

        /**
         * Bind our extended Guzzle client and pass any configured request option.
         * Get HandlerStack from container to allow other service providers, e.g. guzzle-debugbar's provider
         * to allow profiling of all requests.
         */
        $this->app->bind(DrupalJsonApiClient::class, function ($app, array $clientConfig = []) {
            $handler = $this->app->make(HandlerStack::class);

            // If handler was not initialized, do it now.
            if (!$handler->hasHandler()) {
                $handler = HandlerStack::create();
            }
            // Guzzle client
            return new Client(
                array_merge(
                    ['handler' => $handler],
                    config('drupal-jsonapi.request_options', []),
                    $clientConfig
                )
            );
        });
    }
}
