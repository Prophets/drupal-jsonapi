<?php

namespace Prophets\DrupalJsonApi\Console;

use Illuminate\Console\GeneratorCommand;

class RepositoryMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:drupaljsonapi-repository';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new repository class for interacting with the Drupal JSON API.';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Repository';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__ . '/stubs/repository.stub';
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
            return sprintf('JsonApi%sRepository', $name);
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

        if (preg_match('/JsonApi(\w+)Repository/', $name, $matches)) {
            $name = $matches[1];
        }

        return $name;
    }
}
