<?php

namespace Prophets\DrupalJsonApi\Scopes;

use Prophets\DrupalJsonApi\Contracts\DrupalScope;
use Prophets\DrupalJsonApi\Request\DrupalJsonApiRequestBuilder;

class LanguageScope implements DrupalScope
{
    /**
     * @var null
     */
    protected $locale;

    /**
     * LanguageScope constructor.
     * @param null $locale
     */
    public function __construct($locale = null)
    {
        $this->locale = $locale ?: config('app.locale');
    }

    /**
     * Localize the request to the Drupal JSON API.
     * Note: this will not limit resources to the locale, if no resource was found,
     * the API will return the resource in it's "original" language.
     * To limit resources to a specific language add a filter on the resources' field
     * which represents it's locale. For example, for resources based on a Drupal Node resource,
     * use the NodeLangcodeScope.
     * @param DrupalJsonApiRequestBuilder $requestBuilder
     */
    public function apply(DrupalJsonApiRequestBuilder $requestBuilder)
    {
        $requestBuilder->setUriQueryParam('lang_code', $this->locale);
    }
}
