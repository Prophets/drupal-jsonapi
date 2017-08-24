<?php

namespace Prophets\DrupalJsonApi\Request;

use Closure;
use Prophets\DrupalJsonApi\Contracts\DrupalScope;
use Psr\Http\Message\RequestInterface;
use WoohooLabs\Yang\JsonApi\Request\JsonApiRequestBuilder;
use WoohooLabs\Yang\JsonApi\Serializer\SerializerInterface;

class DrupalJsonApiRequestBuilder extends JsonApiRequestBuilder
{
    /**
     * @var array
     */
    protected $filters = [];

    /**
     * @var array
     */
    protected $scopes = [];

    /**
     * @var array
     */
    protected $removedScopes = [];

    /**
     * DrupalJsonApiRequestBuilder constructor.
     * @param RequestInterface $request
     * @param SerializerInterface|null $serializer
     */
    public function __construct(RequestInterface $request, SerializerInterface $serializer = null)
    {
        parent::__construct($request, $serializer);

         $this->setHeader('Accept-Charset', 'utf-8')
            ->setUriQueryParam('_format', 'api_json');
    }

    /**
     * @param array $filter
     * @return JsonApiRequestBuilder
     */
    public function setJsonApiFilter(array $filter): JsonApiRequestBuilder
    {
        return $this->setUriQueryParam('filter', $filter);
    }

    /**
     * Set the request filters apply scopes and return the request.
     * @return RequestInterface
     */
    public function getRequestWithScopes(): RequestInterface
    {
        $requestBuilder = $this->applyScopes();

        return $requestBuilder->getRequest();
    }

    /**
     * @return RequestInterface
     */
    public function getRequest(): RequestInterface
    {
        $this->setJsonApiFilter($this->filters);

        return parent::getRequest();
    }

    /**
     * Add a filter condition to the request filters.
     *
     * @param string $fieldName The path containing the dot separated field names that you wish to filter on.
     * @param null|string $operator The operator to use for comparison.
     *      Can be =, <, >, <>, IN, NOT IN, IS NULL, IS NOT NULL, CONTAINS or BETWEEN.
     * @param null|string $value The value that you will compare against the value held in
     *      the field pointed by the path.
     * @param null|string $group The group this filter is part of.
     * @param null|string $filterId
     * @return $this
     */
    public function addFilter($fieldName, $operator = '=', $value = null, $group = null, $filterId = null)
    {
        $condition = [
            'path' => $fieldName
        ];

        if ($value !== null) {
            $condition['value'] = $value;
        }
        if ($operator !== null) {
            $condition['operator'] = $operator;
        }
        if ($group !== null) {
            $condition['memberOf'] = $group;
        }
        if ($filterId === null) {
            $filterId = str_replace('.', '-', $fieldName);
            $affix = 0;
            $filterKey = $filterId;

            while (array_key_exists($filterKey, $this->filters)) {
                $filterKey = $filterId . '_' . ++$affix;
            }
            $filterId = $filterKey;
        }
        $this->filters[$filterId] = ['condition' => $condition];

        return $this;
    }

    /**
     * Add a filter condition in shorthand style to the request filters.
     * Note: this style of filtering is not recommended as the $fieldName is also the filter's id which needs to be
     * unique. This way the field can only be filtered "once" in shorthand style.
     *
     * @param $fieldName
     * @param $value
     * @return $this
     */
    public function addSimpleFilter($fieldName, $value)
    {
        $this->filters[$fieldName]['value'] = $value;

        return $this;
    }

    /**
     * Add a filter group to the request filters.
     *
     * @param $name
     * @param string $conjunction
     * @param null $parent Add the group to a 'parent' group.
     */
    public function addFilterGroup($name, $conjunction = 'AND', $parent = null)
    {
        $group = [
            'conjunction' => $conjunction
        ];

        if ($parent !== null) {
            $group['group'] = $parent;
        }
        $this->filters[$name] = ['group' => $group];
    }

    /**
     * Register a new global scope.
     *
     * @param  string  $identifier
     * @param  Scope|\Closure  $scope
     * @return $this
     */
    public function withGlobalScope($identifier, $scope)
    {
        $this->scopes[$identifier] = $scope;

        return $this;
    }

    /**
     * Remove a registered global scope.
     *
     * @param  Scope|string  $scope
     * @return $this
     */
    public function withoutGlobalScope($scope)
    {
        if (! is_string($scope)) {
            $scope = get_class($scope);
        }

        unset($this->scopes[$scope]);

        $this->removedScopes[] = $scope;

        return $this;
    }

    /**
     * Remove all or passed registered global scopes.
     *
     * @param  array|null  $scopes
     * @return $this
     */
    public function withoutGlobalScopes(array $scopes = null)
    {
        if (is_array($scopes)) {
            foreach ($scopes as $scope) {
                $this->withoutGlobalScope($scope);
            }
        } else {
            $this->scopes = [];
        }

        return $this;
    }

    /**
     * Get an array of global scopes that were removed from the query.
     *
     * @return array
     */
    public function removedScopes()
    {
        return $this->removedScopes;
    }

    /**
     * Apply the scopes to the request builder instance and return it.
     *
     * @return DrupalJsonApiRequestBuilder|static
     */
    public function applyScopes()
    {
        if (!$this->scopes) {
            return $this;
        }
        $requestBuilder = clone $this;

        foreach ($this->scopes as $scope) {
            if ($scope instanceof Closure) {
                $scope($requestBuilder);
            }
            if ($scope instanceof DrupalScope) {
                $scope->apply($requestBuilder);
            }
        }

        return $requestBuilder;
    }
}
