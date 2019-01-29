<?php

namespace Prophets\DrupalJsonApi\Repositories\Concerns;

use Prophets\DrupalJsonApi\Scopes\ConsumerScope;

trait HasConsumerId
{
    static public function bootHasConsumerId()
    {
        static::addGlobalScope(app(ConsumerScope::class));
    }
}