<?php

namespace Prophets\DrupalJsonApi\Models\Concerns;

use Prophets\DrupalJsonApi\Relations\HasMany;
use Prophets\DrupalJsonApi\Relations\HasManyDirty;
use Prophets\DrupalJsonApi\Relations\HasOne;

trait HasRelationships
{
    /**
     * The relations for the resource.
     *
     * @var array
     */
    protected $relations = [];

    /**
     * Determine if the given relation is loaded.
     *
     * @param  string  $key
     * @return bool
     */
    public function relationLoaded($key)
    {
        return array_key_exists($key, $this->relations);
    }

    /**
     * Get all the loaded relations for the instance.
     *
     * @return array
     */
    public function getRelations()
    {
        return $this->relations;
    }

    /**
     * Get a specified relationship.
     *
     * @param  string  $relation
     * @return mixed
     */
    public function getRelation($relation)
    {
        return $this->relations[$relation];
    }

    /**
     * Set the specific relationship in the model.
     *
     * @param  string  $relation
     * @param  mixed  $value
     * @return $this
     */
    public function setRelation($relation, $value)
    {
        $this->relations[$relation] = $value;

        return $this;
    }

    /**
     * Set the entire relations array on the model.
     *
     * @param  array  $relations
     * @return $this
     */
    public function setRelations(array $relations)
    {
        $this->relations = $relations;

        return $this;
    }

    /**
     * @return string
     */
    protected function getRelationName()
    {
        list($one, $two, $caller) = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);

        return $caller['function'];
    }

    /**
     * @param $repository
     * @return HasOne
     */
    public function hasOne($repository)
    {
        $relation = new HasOne($this, $this->getRelationName(), $repository);
        return $relation;
    }

    /**
     * @param $repository
     * @return HasMany
     */
    public function hasMany($repository)
    {
        $relation = new HasMany($this, $this->getRelationName(), $repository);
        return $relation;
    }

    /**
     * @param $repository
     * @param $classList
     * @return HasManyDirty
     */
    public function hasManyDirty($repository, $classList)
    {
        $relation = new HasManyDirty($this, $this->getRelationName(), $repository);
        $relation->setClassList($classList);
        return $relation;
    }
}
