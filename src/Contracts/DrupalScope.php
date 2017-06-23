<?php

namespace Prophets\DrupalJsonApi\Contracts;

use Prophets\DrupalJsonApi\Request\DrupalJsonApiRequestBuilder;

interface DrupalScope
{
    public function apply(DrupalJsonApiRequestBuilder $requestBuilder);
}
