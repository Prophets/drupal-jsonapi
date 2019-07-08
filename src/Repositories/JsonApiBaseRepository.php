<?php

namespace Prophets\DrupalJsonApi\Repositories;

use Prophets\DrupalJsonApi\Collection;
use Prophets\DrupalJsonApi\Contracts\BaseRelation;
use Prophets\DrupalJsonApi\Contracts\BaseRelationHasMany;
use Prophets\DrupalJsonApi\Contracts\BaseRelationMixed;
use Prophets\DrupalJsonApi\Contracts\BaseRelationSingle;
use Prophets\DrupalJsonApi\Contracts\BaseRepository;
use Prophets\DrupalJsonApi\DrupalJsonApiClient;
use Prophets\DrupalJsonApi\Meta;
use Prophets\DrupalJsonApi\Request\DrupalJsonApiRequestBuilder;
use Prophets\DrupalJsonApi\Model;
use GuzzleHttp\Psr7\Request;
use Http\Adapter\Guzzle6\Client;
use Illuminate\Support\Arr;
use Prophets\DrupalJsonApi\ResourceIdentifierCollection;
use Prophets\DrupalJsonApi\ResourceIdentifierObject;
use WoohooLabs\Yang\JsonApi\Client\JsonApiClient;
use WoohooLabs\Yang\JsonApi\Response\JsonApiResponse;
use WoohooLabs\Yang\JsonApi\Schema\ResourceObject;

class JsonApiBaseRepository implements BaseRepository
{
    use Concerns\HasGlobalScopes;
    use Concerns\HasConsumerId;

    /**
     * The array of booted repositories.
     *
     * @var array
     */
    protected static $booted = [];

    /**
     * @var Model
     */
    protected $model;

    /**
     * @var Client
     */
    private $httpClient;

    /**
     * @var string
     */
    private $apiBaseUrl;

    /**
     * @var boolean
     */
    protected $authEnabled = false;

    /**
     * @var boolean
     */
    protected $withoutIncludes = false;

    /**
     * JsonApiBaseRepository constructor.
     * @param Model|string $model
     * @param array $params
     */
    public function __construct($model, array $params = [])
    {
        $this->model = is_string($model) ? new $model : $model;

        if (! $this->model instanceof Model) {
            throw new \InvalidArgumentException('Model must be an instance of ' . Model::class);
        }
        $this->setParams($params);
        $this->bootIfNotBooted();
    }

    /**
     * Set repository parameters.
     *
     * @param array $params
     */
    protected function setParams(array $params)
    {
        $this->apiBaseUrl = $params['baseUrl'] ?? config('drupal-jsonapi.base_url');

        if (isset($params['authEnabled'])) {
            $this->setAuthEnabled($params['authEnabled']);
        }
        if (isset($params['withoutIncludes'])) {
            $this->setWithoutIncludes($params['withoutIncludes']);
        }
    }

    /**
     * Check if the repository needs to be booted and if so, do it.
     *
     * @return void
     */
    protected function bootIfNotBooted()
    {
        if (! isset(static::$booted[static::class])) {
            static::$booted[static::class] = true;
            static::boot();
        }
    }

    /**
     * The "booting" method of the repository.
     *
     * @return void
     */
    protected static function boot()
    {
        static::bootTraits();
    }

    /**
     * Boot all of the bootable traits on the repository.
     *
     * @return void
     */
    protected static function bootTraits()
    {
        $class = static::class;

        foreach (class_uses_recursive($class) as $trait) {
            if (method_exists($class, $method = 'boot'.class_basename($trait))) {
                forward_static_call([$class, $method]);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getNewModel()
    {
        return new $this->model;
    }

    /**
     * @return DrupalJsonApiRequestBuilder
     */
    protected function newRequestBuilderWithoutScopes()
    {
        $requestBuilder = new DrupalJsonApiRequestBuilder((new Request('', '')));
        $requestBuilder
            ->setProtocolVersion('1.1')
            ->setMethod('GET');

        return $requestBuilder;
    }

    /**
     * Get a request initialized with all global scopes.
     *
     * @param string $path
     * @return DrupalJsonApiRequestBuilder
     */
    protected function newRequestBuilder($path = '')
    {
        $requestBuilder = $this->newRequestBuilderWithoutScopes();
        $uri = $this->getResourceUriForModel($this->model);

        if (! empty($path)) {
            $uri .= '/' . $path;
        }
        $requestBuilder->setUri($uri);

        foreach ($this->getGlobalScopes() as $id => $scope) {
            $requestBuilder->withGlobalScope($id, $scope);
        }
        $resourceFields = $this->getResourceFields($this->model);
        $requestBuilder->setJsonApiFields($resourceFields['fields']);

        if (count($resourceFields['includes'])) {
            $requestBuilder->setJsonApiIncludes($resourceFields['includes']);
        }

        return $requestBuilder;
    }

    /**
     * Get a request for retrieving a resource for a relation.
     *
     * @param BaseRelation $relation
     * @return DrupalJsonApiRequestBuilder
     */
    protected function newRelationRequestBuilder(BaseRelation $relation)
    {
        $relatedModel = $relation->getRelatedModel();
        $requestBuilder = $this->newRequestBuilderWithoutScopes();
        $requestBuilder->setUri(
            $this->getResourceUriForModel($relatedModel)
            . '/' . $relatedModel->getId()
        );

        foreach ($this->getGlobalScopes() as $id => $scope) {
            $requestBuilder->withGlobalScope($id, $scope);
        }

        if ($relation instanceof BaseRelationMixed) {
            foreach ($relation->getClassList() as $modelClass) {
                $model = new $modelClass;

                if (! $model instanceof Model) {
                    throw new \InvalidArgumentException('Model class must be instance of ' . Model::class);
                }
                $this->getResourceFields($model, $relation->getName(), $resourceFields);
            }
        } elseif ($relation instanceof BaseRelationSingle) {
            $resourceFields = $this->getResourceFields($relation->getNewModel(), $relation->getName());
        } else {
            throw new \RuntimeException('Relation is not implemented.');
        }
        $requestBuilder->setJsonApiFields($resourceFields['fields']);

        if (count($resourceFields['includes'])) {
            $requestBuilder->setJsonApiIncludes($resourceFields['includes']);
        }

        return $requestBuilder;
    }

    /**
     * @param Model $model
     * @return string
     */
    protected function getResourceUriForModel(Model $model)
    {
        $uri = $this->apiBaseUrl . '/' . str_replace_first('--', '/', $model->getResourceName());

        return $uri;
    }

    /**
     * @return Client
     */
    protected function getHttpClient()
    {
        if ($this->httpClient === null) {
            $options = [];

            if ($this->isAuthEnabled()) {
                $user = config('drupal-jsonapi.auth.user');
                $password = config('drupal-jsonapi.auth.password');

                if (!empty($user) && !empty($password)) {
                    $options['auth'] = [$user, $password];
                }
            }
            $this->httpClient = new Client(app(DrupalJsonApiClient::class, $options));
        }
        return $this->httpClient;
    }

    /**
     * @param DrupalJsonApiRequestBuilder $requestBuilder
     * @return \WoohooLabs\Yang\JsonApi\Response\JsonApiResponse
     */
    protected function executeRequest(DrupalJsonApiRequestBuilder $requestBuilder)
    {
        $jsonApiClient = new JsonApiClient($this->getHttpClient());
        $request = $requestBuilder->getRequestWithScopes();

        return $jsonApiClient->sendRequest($request);
    }

    /**
     * Map the JsonApi ResourceObject to an application model.
     * @param ResourceObject $resource
     * @param null|Model|BaseRelation $model
     * @return Model
     */
    protected function mapResourceToModel(ResourceObject $resource, $model = null)
    {
        if ($model instanceof BaseRelation) {
            $model = $this->getModelResolverForRelation($model)($resource);
        }
        if (! $model instanceof Model) {
            throw new \RuntimeException('Not a valid Model.');
        }
        if ($resource->hasMeta()) {
            $model->setMeta(new Meta($resource->meta()));
        }
        $model->populate($resource->attributes());
        $model->setId($resource->id());

        foreach ($resource->relationships() as $relationship) {
            if (! method_exists($model, $relationship->name())) {
                continue;
            }
            $resourceLinks = $relationship->resourceLinks();
            $resourceIdentifierCollection = new ResourceIdentifierCollection();

            foreach ($resourceLinks as $resourceLink) {
                $resourceIdentifierObject = ResourceIdentifierObject::createFromResourceLink($resourceLink);
                $resourceLinkMeta = $relationship->resourceLinkMeta($resourceIdentifierObject->type(), $resourceIdentifierObject->id());

                if ($resourceLinkMeta !== null) {
                    $resourceIdentifierObject->setMeta(new Meta($resourceLinkMeta));
                }
                $resourceIdentifierCollection[] = $resourceIdentifierObject;
            }
            if ($resourceIdentifierCollection->isNotEmpty()) {
                $model->setRelationResourceIdentifiers($relationship->name(), $resourceIdentifierCollection);
            }
        }
        foreach ($model->getIncludes() as $fieldName) {
            $modelRelation = $model->$fieldName();
            $relationValue = null;

            if (($relationship = $resource->relationship($fieldName)) !== null) {
                $resourceMap = $relationship->resourceMap();

                if (count($resourceMap) > 0) {
                    if ($relationship->isToManyRelationship()) {
                        $relationValue = $this->mapResourcesToCollection(
                            $relationship->resources(),
                            $modelRelation
                        );
                    } else {
                        $relationValue = $this->mapResourceToModel(
                            $relationship->resource(),
                            $modelRelation
                        );
                    }
                }
            }
            if ($relationValue === null && $modelRelation instanceof BaseRelationHasMany) {
                $relationValue = new Collection();
            }
            $model->setRelation($fieldName, $relationValue);
        }

        return $model;
    }

    /**
     * Map primary resources from the JsonApi response document to models defined in the map list.
     * @param array $resources
     * @param null|BaseRelation $relation
     * @return Collection
     */
    protected function mapResourcesToCollection(array $resources, $relation = null)
    {
        $collection = new Collection();
        $model = $relation instanceof BaseRelation
            ? $this->getModelResolverForRelation($relation)
            : function () {
                return $this->getNewModel();
            };

        foreach ($resources as $resource) {
            $newModel = $model($resource);

            if ($newModel !== null) {
                $collection[] = $this->mapResourceToModel($resource, $newModel);
            }
        }
        return $collection;
    }

    /**
     * @param BaseRelation $relation
     * @return \Closure
     */
    protected function getModelResolverForRelation(BaseRelation $relation)
    {
        if ($relation instanceof BaseRelationMixed) {
            $map = $relation->getClassList();
            $modelResolver = function ($resource) use ($map) {
                $modelClass = Arr::first($map, function ($model) use ($resource) {
                    return $model::getResourceName() == $resource->type();
                });

                if (! $modelClass) {
                    return null;
                }
                return new $modelClass;
            };
        } elseif ($relation instanceof BaseRelationSingle) {
            $modelResolver = function () use ($relation) {
                return $relation->getNewModel();
            };
        } else {
            throw new \RuntimeException('Relation not implemented.');
        }

        return $modelResolver;
    }

    /**
     * Retrieve 'fields' and 'includes' for a model and it's relations.
     * @param Model $model
     * @param null $field
     * @param array $fields
     * @return array
     */
    protected function getResourceFields(Model $model, $field = null, &$fields = null)
    {
        if (empty($fields)) {
            $fields = [
                'fields' => [],
                'includes' => []
            ];
        }
        if (! $this->isWithoutIncludes()) {
            $includes = $model->getIncludes();

            foreach ($includes as $relationField) {
                if (! method_exists($model, $relationField)) {
                    continue;
                }
                $relation = $model->$relationField();

                if ($relation instanceof BaseRelation) {
                    $relationModels = $relation instanceof BaseRelationMixed ? array_map(function ($modelClass) {
                        return new $modelClass;
                    }, $relation->getClassList()) : [$relation->getNewModel()];

                    foreach ($relationModels as $relationModel) {
                        $this->getResourceFields(
                            $relationModel,
                            ($field ? $field . '.' . $relationField : $relationField),
                            $fields
                        );
                    }
                }
            }
            if ($field !== null && !in_array($field, $fields['includes'])
                && ! array_first($fields['includes'], function ($value) use ($field) {
                    return starts_with($value, $field . '.');
                })) {
                $fields['includes'][] = $field;
            }
        }
        if (! isset($fields['fields'][$model->getResourceName()])) {
            $fields['fields'][$model->getResourceName()] = $model->getFields();
        }

        return $fields;
    }

    /**
     * Map primary resource from the JsonApi response to our model.
     * @param JsonApiResponse $response
     * @param null|BaseRelationSingle $relation
     * @return Model
     */
    protected function responseToModel(JsonApiResponse $response, $relation = null)
    {
        if (! $response->isSuccessfulDocument([200])
            || ! $response->document()->hasAnyPrimaryResources()) {
            return null;
        }
        $model = $relation ? $relation : $this->getNewModel();

        return $this->mapResourceToModel($response->document()->primaryResource(), $model);
    }

    /**
     * Map primary resource from the JsonApi response to a collection of models.
     * @param JsonApiResponse $response
     * @param null|BaseRelation $relation
     * @return Collection
     */
    protected function responseToCollection(JsonApiResponse $response, $relation = null)
    {
        if (! $response->isSuccessfulDocument([200])) {
            return new Collection();
        }

        return $this->mapResourcesToCollection($relation ? $response->document()->includedResources() : $response->document()->primaryResources(), $relation);
    }

    /**
     * @param DrupalJsonApiRequestBuilder $requestBuilder
     * @param array $attributes
     * @return DrupalJsonApiRequestBuilder
     */
    protected function filterRequestByAttributes(
        DrupalJsonApiRequestBuilder $requestBuilder,
        array $attributes = []
    ) {
        foreach ($attributes as $key => $value) {
            if (! is_array($value)) {
                $requestBuilder->addSimpleFilter($key, $value);
            } else {
                call_user_func_array([$requestBuilder, 'addFilter'], $value);
            }
        }

        return $requestBuilder;
    }

    /**
     * @inheritdoc
     */
    public function find($id)
    {
        $response = $this->executeRequest($this->newRequestBuilder($id));

        return $this->responseToModel($response);
    }

    /**
     * @inheritdoc
     */
    public function findForRelation(BaseRelation $relation)
    {
        $response = $this->executeRequest($this->newRelationRequestBuilder($relation));

        return $this->responseToModel($response, $relation);
    }

    /**
     * @inheritdoc
     */
    public function findByAttributes(array $attributes)
    {
        $requestBuilder = $this->newRequestBuilder();
        $response = $this->executeRequest(
            $this->filterRequestByAttributes($requestBuilder, $attributes)
        );

        return $this->responseToModel($response);
    }

    /**
     * Note: Inverted sorting direction is defined by
     * prefixing the column name with - in the $orderyBy argument.
     * @inheritdoc BaseRepository::getByAttributes()
     */
    public function getByAttributes(array $attributes, $orderBy = null, $page = null)
    {
        $requestBuilder = $this->newRequestBuilder();
        $this->filterRequestByAttributes($requestBuilder, $attributes);

        if ($orderBy !== null) {
            $requestBuilder->setJsonApiSort($orderBy);
        }

        if ($page !== null) {
            $requestBuilder->setJsonApiPage($page);
        }
        $response = $this->executeRequest($requestBuilder);

        return $this->responseToCollection($response);
    }

    /**
     * @inheritdoc
     */
    public function getByIds(array $ids)
    {
        $requestBuilder = $this->newRequestBuilder();
        $requestBuilder->addFilter('id', 'IN', $ids);
        $response = $this->executeRequest($requestBuilder);

        return $this->responseToCollection($response);
    }

    /**
     * @inheritdoc
     */
    public function getForRelation(BaseRelation $relation)
    {
        $response = $this->executeRequest($this->newRelationRequestBuilder($relation));

        return $this->responseToCollection($response, $relation);
    }

    /**
     * @return bool
     */
    public function clearCache()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isAuthEnabled(): bool
    {
        return $this->authEnabled;
    }

    /**
     * @param bool $authEnabled
     * @return JsonApiBaseRepository
     */
    public function setAuthEnabled(bool $authEnabled): JsonApiBaseRepository
    {
        $this->authEnabled = $authEnabled;
        return $this;
    }

    /**
     * @return bool
     */
    public function isWithoutIncludes(): bool
    {
        return $this->withoutIncludes;
    }

    /**
     * @param bool $withoutIncludes
     * @return JsonApiBaseRepository
     */
    public function setWithoutIncludes(bool $withoutIncludes): JsonApiBaseRepository
    {
        $this->withoutIncludes = (bool) $withoutIncludes;
        return $this;
    }
}
