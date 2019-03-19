<?php

namespace Prophets\DrupalJsonApi\Contracts;

use Prophets\DrupalJsonApi\Meta;

interface HasMeta
{
    /**
     * Set the model's meta property.
     *
     * @param Meta $meta
     */
    public function setMeta(Meta $meta);

    /**
     * Get the model's meta property.
     *
     * @return Meta
     */
    public function getMeta();

    /**
     * Check if the model has a meta object.
     *
     * @return bool
     */
    public function hasMeta();
}
