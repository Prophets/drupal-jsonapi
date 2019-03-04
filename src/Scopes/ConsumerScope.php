<?php

namespace Prophets\DrupalJsonApi\Scopes;

use Prophets\DrupalJsonApi\Contracts\DrupalScope;
use Prophets\DrupalJsonApi\Request\DrupalJsonApiRequestBuilder;

/**
 * Add a consumer ID to requests for features using
 * Drupal Consumer module.
 *
 * @see https://www.drupal.org/project/consumers
 * @package Prophets\DrupalJsonApi\Scopes
 */
class ConsumerScope implements DrupalScope
{
    /**
     * @var string
     */
    protected $consumerId;

    /**
     * ConsumerScope constructor.
     *
     * @param null|string $consumerId
     */
    public function __construct($consumerId = null)
    {
        $this->consumerId = $consumerId ?: config('drupal-jsonapi.consumer_id');
    }

    /**
     * @param DrupalJsonApiRequestBuilder $requestBuilder
     */
    public function apply(DrupalJsonApiRequestBuilder $requestBuilder)
    {
        if (! empty($this->consumerId)) {
            $requestBuilder->setUriQueryParam('_consumer_id', $this->consumerId);
        }
    }
}
