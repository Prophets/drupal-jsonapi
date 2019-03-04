<?php

namespace Prophets\DrupalJsonApi\Concerns;

use Prophets\DrupalJsonApi\Meta;

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
     */
    public function setMeta(Meta $meta)
    {
        $this->meta = $meta;
    }

    /**
     * Get the model's meta property.
     *
     * @return Meta
     */
    public function getMeta()
    {
        if (! $this->hasMeta()) {
            throw new \InvalidArgumentException("No meta object available.");
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
