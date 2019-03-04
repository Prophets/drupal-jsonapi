<?php

namespace Prophets\DrupalJsonApi\Models\Concerns;

use Prophets\DrupalJsonApi\Contracts\BaseRelationHasMany;
use Prophets\DrupalJsonApi\Contracts\BaseRelationHasOne;
use Prophets\DrupalJsonApi\Contracts\ResourceIdentifierObject;
use Prophets\DrupalJsonApi\Relations\HasMany;
use Prophets\DrupalJsonApi\Relations\HasManyMixed;
use Prophets\DrupalJsonApi\Relations\HasOne;
use Prophets\DrupalJsonApi\Relations\HasOneMixed;
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

    /**
     * @param $relation
     * @param ResourceIdentifierObject $resourceIdentifierObject
     */
    public function setRelationResourceIdentifier($relation, ResourceIdentifierObject $resourceIdentifierObject)
    {
        if (! $this->$relation() instanceof BaseRelationHasOne) {
            throw new \LogicException('Relationship must be of type Prophets\DrupalJsonApi\Contracts\BaseRelationHasOne');
        }
        $this->relationMeta[$relation] = $resourceIdentifierObject;
    }

    /**
     * @param $relation
     *
     * @return null|ResourceIdentifierObject
     */
    public function getRelationResource($relation)
    {
        return $this->relationMeta[$relation] ?: null;
    }

    /**
     * @param $relation
     * @param ResourceIdentifierCollection $resourceIdentifierCollection
     */
    public function setRelationResourceIdentifiers($relation, ResourceIdentifierCollection $resourceIdentifierCollection)
    {
        if ($this->$relation() instanceof BaseRelationHasOne) {
            $resourceIdentifierObject = $resourceIdentifierCollection->shift();
            $this->setRelationResourceIdentifier($relation, $resourceIdentifierObject);
            return;
        }
        if (! $this->$relation() instanceof BaseRelationHasMany) {
            throw new \LogicException('Relationship must be of type Prophets\DrupalJsonApi\Contracts\BaseRelationHasMany');
        }
        $this->relationMeta[$relation] = $resourceIdentifierCollection;
    }

    /**
     * @param $relation
     *
     * @return null|ResourceIdentifierCollection
     */
    public function getRelationResources($relation)
    {
        return $this->relationMeta[$relation] ?: null;
    }
}
