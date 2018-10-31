<?php

namespace Prophets\DrupalJsonApi\Relations;

use Prophets\DrupalJsonApi\Contracts\BaseRelationSingle;
use Prophets\DrupalJsonApi\Contracts\BaseRepository;
use Prophets\DrupalJsonApi\Model;

abstract class HasOneManySingle extends Relation implements BaseRelationSingle
{
    /**
     * @var string
     */
    protected $modelClass;

    /**
     * HasOne constructor.
     * @param Model $relatedModel
     * @param string $name
     * @param $modelClass
     */
    public function __construct(Model $relatedModel, $name, $modelClass)
    {
        parent::__construct($relatedModel, $name);
        $this->modelClass = $modelClass;
    }

    /**
     * @return Model
     */
    public function getNewModel(): Model
    {
        return new $this->modelClass;
    }

    /**
     * @return BaseRepository
     */
    public function getRepository(): BaseRepository
    {
        return call_user_func([$this->modelClass, 'getRepository']);
    }
}
