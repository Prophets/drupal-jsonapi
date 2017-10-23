<?php

namespace Prophets\DrupalJsonApi\Models\Concerns;

use Prophets\DrupalJsonApi\Relations\HasMany;
use Prophets\DrupalJsonApi\Relations\HasManyMixed;
use Prophets\DrupalJsonApi\Relations\HasOne;
use Prophets\DrupalJsonApi\Relations\HasOneMixed;

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
     * @param string $modelClass
     * @return HasOne
     */
    public function hasOne($modelClass)
    {
        $relation = new HasOne($this, $this->getRelationName(), $modelClass);
        return $relation;
    }

    /**
     * @param array $classList
     * @return HasOneMixed
     */
    public function hasOneMixed(array $classList)
    {
        $relation = new HasOneMixed($this, $this->getRelationName());
        $relation->setClassList($classList);
        return $relation;
    }

    /**
     * @param string $modelClass
     * @return HasMany
     */
    public function hasMany($modelClass)
    {
        $relation = new HasMany($this, $this->getRelationName(), $modelClass);
        return $relation;
    }

    /**
     * @param array $classList
     * @return HasManyMixed
     */
    public function hasManyMixed(array $classList)
    {
        $relation = new HasManyMixed($this, $this->getRelationName());
        $relation->setClassList($classList);
        return $relation;
    }
}
