<?php

namespace Prophets\DrupalJsonApi\Console;

use Illuminate\Console\GeneratorCommand;

class CacheDecoratorMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:drupaljsonapi-cachedecorator';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new cache decorator for Drupal JSON API repository.';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'CacheDecorator';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__ . '/stubs/cachedecorator.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\Repositories\\' . $this->getEntityNameInput();
    }

    /**
     * Get the desired class name from the input.
     *
     * @return string
     */
    protected function getNameInput()
    {
        $name = trim($this->argument('name'));

        if ($name === $this->getEntityNameInput()) {
            return sprintf('JsonApi%sCacheDecorator', $name);
        }

        return $name;
    }

    /**
     * Get the entity name retrieved from the name input
     *
     * @return string
     */
    protected function getEntityNameInput()
    {
        $name = trim($this->argument('name'));

        if (preg_match('/JsonApi(\w+)CacheDecorator/', $name, $matches)) {
            $name = $matches[1];
        }

        return $name;
    }

    /**
     * @inheritdoc
     */
    protected function buildClass($name)
    {
        $stub = parent::buildClass($name);

        return $this->replaceInterface($stub);
    }

    /**
     * Replace the interface.
     *
     * @param $stub
     * @return mixed
     */
    protected function replaceInterface($stub)
    {
        $interface = sprintf('JsonApi%sInterface', $this->getEntityNameInput());

        return str_replace('DummyInterface', $interface, $stub);
    }
}
