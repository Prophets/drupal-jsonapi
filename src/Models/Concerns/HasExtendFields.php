<?php

namespace Prophets\DrupalJsonApi\Models\Concerns;

trait HasExtendFields
{
    /**
     * @var array
     */
    protected $extendFields = [];

    /**
     * Extend the model's defined fields.
     */
    protected function extendFields()
    {
        $this->fields = array_merge($this->fields, $this->extendFields);
    }
}
