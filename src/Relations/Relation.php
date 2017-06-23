<?php

namespace Prophets\DrupalJsonApi\Relations;

use Prophets\DrupalJsonApi\Contracts\BaseRelation;
use Prophets\DrupalJsonApi\Contracts\BaseRepository;
use Prophets\DrupalJsonApi\Model;

abstract class Relation implements BaseRelation
{
    /**
     * @var string
     */
    protected $repositoryClass;
    /**
     * @var string
     */
    protected $name;
    /**
     * @var Model
     */
    protected $relatedModel;
    /**
     * @var BaseRepository
     */
    protected $repository;

    /**
     * Relation constructor.
     * @param Model $relatedModel
     * @param string $name
     * @param string $repositoryClass
     */
    public function __construct(Model $relatedModel, string $name, string $repositoryClass)
    {
        $this->relatedModel = $relatedModel;
        $this->name = $name;
        $this->repositoryClass = $repositoryClass;
    }

    /**
     * @return BaseRepository
     */
    public function getRepository(): BaseRepository
    {
        if ($this->repository === null) {
            $repository = app($this->repositoryClass);

            if (! $repository instanceof BaseRepository) {
                throw new \RuntimeException('Class does not implement BaseRepository.');
            }
            $this->repository = $repository;
        }

        return $this->repository;
    }

    /**
     * @return Model
     */
    public function getNewModel(): Model
    {
        return $this->getRepository()->getNewModel();
    }

    /**
     * @return Model
     */
    public function getRelatedModel(): Model
    {
        return $this->relatedModel;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}
