<?php

namespace Prophets\DrupalJsonApi\Contracts;

use Prophets\DrupalJsonApi\Meta;

interface ResourceIdentifierObject
{
    /**
     * Get the resource type member.
     *
     * @return string
     */
    public function type(): string;

    /**
     * Get the resource identifier.
     *
     * @return string
     */
    public function id(): string;

    /**
     * Get the resource meta
     *
     * @return Meta
     */
    public function meta(): Meta;
}
