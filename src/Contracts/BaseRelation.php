<?php

namespace Prophets\DrupalJsonApi\Contracts;

use Prophets\DrupalJsonApi\Model;

interface BaseRelation
{
    /**
     * @return BaseRepository
     */
    public function getRepository(): BaseRepository;

    /**
     * @return Model
     */
    public function getRelatedModel(): Model;

    /**
     * @return string
     */
    public function getName(): string;
}
