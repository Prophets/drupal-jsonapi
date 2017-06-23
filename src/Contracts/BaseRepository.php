<?php

namespace Prophets\DrupalJsonApi\Contracts;

interface BaseRepository
{
    /**
     * Get a new instance of the repository model.
     * @return \Prophets\DrupalJsonApi\Model
     */
    public function getNewModel();

    /**
     * Get a single resource by id.
     * @param  int $id
     * @return \Prophets\DrupalJsonApi\Model|null
     */
    public function find($id);

    /**
     * Get a resource by an array of attributes.
     * @param  array  $attributes
     * @return \Prophets\DrupalJsonApi\Model|null
     */
    public function findByAttributes(array $attributes);

    /**
     * Get a related resource by the relation defined in the repository.
     * @param BaseRelation $relation
     * @return null|\Prophets\DrupalJsonApi\Model
     */
    public function findForRelation(BaseRelation $relation);

    /**
     * Get resources by an array of attributes.
     * @param array $attributes
     * @param null|string|array $orderBy
     * @param null $page
     * @return \Prophets\DrupalJsonApi\Collection
     */
    public function getByAttributes(array $attributes, $orderBy = null, $page = null);

    /**
     * Get resources by an array of id's.
     * @param array $ids
     * @return \Prophets\DrupalJsonApi\Collection
     */
    public function getByIds(array $ids);

    /**
     * Get related resource collection.
     * @param BaseRelation $relation
     * @return \Prophets\DrupalJsonApi\Collection
     */
    public function getForRelation(BaseRelation $relation);

    /**
     * Clear the cache for this repository's entity
     * @return bool
     */
    public function clearCache();
}
