<?php

namespace Prophets\DrupalJsonApi;

use GuzzleHttp\HandlerStack;
use Illuminate\Support\ServiceProvider;
use Prophets\DrupalJsonApi\Contracts\RepositoryModels;
use Prophets\DrupalJsonApi\Contracts\RepositoryFactory as RepositoryFactoryContract;
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

        app(DrupalJsonApi::class)->boot();
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(RepositoryFactoryContract::class, RepositoryFactory::class);
        $this->app->bind(RepositoryModels::class, RepositoryModelCollection::class);
        $this->app->singleton(DrupalJsonApi::class);

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
            return new DrupalJsonApiClient(
                array_merge(
                    ['handler' => $handler],
                    config('drupal-jsonapi.request_options', []),
                    $clientConfig
                )
            );
        });
    }
}
