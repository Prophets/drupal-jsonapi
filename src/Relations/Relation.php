<?php

namespace Prophets\DrupalJsonApi\Relations;

use Prophets\DrupalJsonApi\Contracts\BaseRelation;
use Prophets\DrupalJsonApi\Contracts\BaseRepository;
use Prophets\DrupalJsonApi\Model;
use Prophets\DrupalJsonApi\Concerns\HasMeta;
use Prophets\DrupalJsonApi\Contracts\HasMeta as HasMetaContract;

abstract class Relation implements BaseRelation, HasMetaContract
{
    use HasMeta;

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
     */
    public function __construct(Model $relatedModel, string $name)
    {
        $this->relatedModel = $relatedModel;
        $this->name = $name;
    }

    /**
     * @return BaseRepository
     */
    public function getRepository(): BaseRepository
    {
        return call_user_func([$this->relatedModel, 'getRepository']);
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
