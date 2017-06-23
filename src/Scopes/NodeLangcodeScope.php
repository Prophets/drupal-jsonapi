<?php

namespace Prophets\DrupalJsonApi\Scopes;

use Prophets\DrupalJsonApi\Contracts\DrupalScope;
use Prophets\DrupalJsonApi\Request\DrupalJsonApiRequestBuilder;

class NodeLangcodeScope implements DrupalScope
{
    /**
     * @var null
     */
    protected $locale;

    /**
     * NodeLangcodeScope constructor.
     * @param null $locale
     */
    public function __construct($locale = null)
    {
        $this->locale = $locale ?: config('app.locale');
    }

    /**
     * Limit node result to current language.
     * @param DrupalJsonApiRequestBuilder $requestBuilder
     */
    public function apply(DrupalJsonApiRequestBuilder $requestBuilder)
    {
        $requestBuilder->addSimpleFilter('langcode', $this->locale);
    }
}
