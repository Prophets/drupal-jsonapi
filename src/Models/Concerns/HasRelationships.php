<?php

namespace Prophets\DrupalJsonApi\Models\Concerns;

use Prophets\DrupalJsonApi\Collection;
use Prophets\DrupalJsonApi\Relations\HasMany;
use Prophets\DrupalJsonApi\Relations\HasManyMixed;
use Prophets\DrupalJsonApi\Relations\HasOne;
use Prophets\DrupalJsonApi\Relations\HasOneMixed;
use Prophets\DrupalJsonApi\Relations\Relation;
use Prophets\DrupalJsonApi\ResourceIdentifierCollection;

trait HasRelationships
{
    /**
     * The relations for the resource.
     *
     * @var array
     */
    protected $relations = [];

    /**
     * The meta information for relations to resources.
     *
     * @var array
     */
    protected $relationMeta = [];

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
     * Pass meta information from matching resource identifiers.
     *
     * @param  string  $relation
     * @param  mixed  $value
     * @return $this
     */
    public function setRelation($relation, $value)
    {
        if (($resourceIdentifierCollection = $this->getRelationResourceIdentifiers($relation)) !== null) {
            $resourceIdentifierCollection->filter(function ($value) {
                return $value->hasMeta();
            })->each(function ($resourceIdentfierObject) use ($value) {
                if ($value instanceof Collection) {
                    $value = $value->find($resourceIdentfierObject->type(), $resourceIdentfierObject->id());
                }
                $value->setMeta($resourceIdentfierObject->meta());
            });
        }
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

    /**
     * @param $relation
     * @param ResourceIdentifierCollection $resourceIdentifierCollection
     */
    public function setRelationResourceIdentifiers($relation, ResourceIdentifierCollection $resourceIdentifierCollection)
    {
        if (! $this->$relation() instanceof Relation) {
            throw new \LogicException('Relationship must be of type ' . Relation::class);
        }
        $this->relationMeta[$relation] = $resourceIdentifierCollection;
    }

    /**
     * @param $relation
     *
     * @return null|ResourceIdentifierObject
     */
    public function getRelationResourceIdentifier($relation)
    {
        return $this->relationMeta[$relation] ? $this->relationMeta[$relation]->first() : null;
    }

    /**
     * @param $relation
     *
     * @return null|ResourceIdentifierCollection
     */
    public function getRelationResourceIdentifiers($relation)
    {
        return $this->relationMeta[$relation] ?? null;
    }
}
