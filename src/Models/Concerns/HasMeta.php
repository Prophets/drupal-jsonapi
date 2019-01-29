<?php

namespace Prophets\DrupalJsonApi\Models\Concerns;

use http\Exception\InvalidArgumentException;
use Prophets\DrupalJsonApi\Meta;
use Prophets\DrupalJsonApi\Model;

trait HasMeta
{
    /**
     * @var null|Meta
     */
    protected $meta;

    /**
     * Set the model's meta property.
     *
     * @param Meta $meta
     *
     * @return Model
     */
    public function setMeta(Meta $meta)
    {
        $this->meta = $meta;

        return $this;
    }

    /**
     * Get the model's meta property.
     *
     * @return Meta
     */
    public function getMeta()
    {
        if (! $this->hasMeta()) {
            throw new InvalidArgumentException('No meta object available.');
        }
        return $this->meta;
    }

    /**
     * Check if the model has a meta object.
     *
     * @return bool
     */
    public function hasMeta()
    {
        return $this->meta instanceof Meta;
    }
}
