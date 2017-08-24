<?php

namespace Prophets\DrupalJsonApi\Repositories;

use Prophets\DrupalJsonApi\Collection;
use Prophets\DrupalJsonApi\Contracts\BaseRelation;
use Prophets\DrupalJsonApi\Contracts\BaseRelationHasMany;
use Prophets\DrupalJsonApi\Contracts\BaseRelationMixed;
use Prophets\DrupalJsonApi\Contracts\BaseRelationSingle;
use Prophets\DrupalJsonApi\Contracts\BaseRepository;
use Prophets\DrupalJsonApi\Meta;
use Prophets\DrupalJsonApi\Request\DrupalJsonApiRequestBuilder;
use Prophets\DrupalJsonApi\Model;
use GuzzleHttp\Psr7\Request;
use Http\Adapter\Guzzle6\Client;
use Illuminate\Support\Arr;
use WoohooLabs\Yang\JsonApi\Client\JsonApiClient;
use WoohooLabs\Yang\JsonApi\Response\JsonApiResponse;
use WoohooLabs\Yang\JsonApi\Schema\ResourceObject;

class JsonApiBaseRepository implements BaseRepository
{
    use Concerns\HasGlobalScopes;

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
     * @var string
     */
    private $apiBaseUrl;

    /**
     * JsonApiBaseRepository constructor.
     * @param Model|string $model
     * @param null|string $apiBaseUrl
     */
    public function __construct(Model $model, $apiBaseUrl = null)
    {
        $this->model = is_string($model) ? new $model : $model;

        if (! $this->model instanceof Model) {
            throw new \InvalidArgumentException('Model must be an instance of ' . Model::class);
        }
        $this->apiBaseUrl = $apiBaseUrl ?: config('drupal-jsonapi.base_url');

        $this->bootIfNotBooted();
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
        $requestBuilder->setJsonApiIncludes($resourceFields['includes']);

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
            . '/' . $relation->getName()
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
                $this->getResourceFields($model, null, $resourceFields);
            }
        } elseif ($relation instanceof BaseRelationSingle) {
            $resourceFields = $this->getResourceFields($relation->getNewModel());
        } else {
            throw new \RuntimeException('Relation is not implemented.');
        }
        $requestBuilder->setJsonApiFields($resourceFields['fields']);
        $requestBuilder->setJsonApiIncludes($resourceFields['includes']);

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
     * @param DrupalJsonApiRequestBuilder $requestBuilder
     * @return \WoohooLabs\Yang\JsonApi\Response\JsonApiResponse
     */
    protected function executeRequest(DrupalJsonApiRequestBuilder $requestBuilder)
    {
        $client = new Client(app(\GuzzleHttp\Client::class));
        $jsonApiClient = new JsonApiClient($client);
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
        $model->populate($resource->attributes());
        $model->setId($resource->id());

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
                    // If a relationship contains meta information,
                    // store it in the related model 'relation_meta' attribute.
                    Collection::make($relationValue instanceof Model ? [$relationValue] : $relationValue)
                        ->each(function (Model $model) use ($relationship) {
                            $meta = $relationship->resourceLinkMeta(
                                $model->getResourceName(),
                                $model->getId()
                            );
                            if (! empty($meta)) {
                                $model->setAttribute('relation_meta', new Meta($meta));
                            }
                        });
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
        if (! $field || ! array_first($fields['includes'], function ($value) use ($field) {
            return starts_with($value, $field . '.');
        })) {
            $fields['includes'] = array_unique(array_merge(array_map(function ($value) use ($field) {
                return $field ? $field . '.' . $value : $value;
            }, $model->getIncludes()), $fields['includes']));
        }
        if (isset($fields['fields'][$model->getResourceName()])) {
            return $fields;
        }
        $fields['fields'][$model->getResourceName()] = $model->getFields();

        foreach ($model->getIncludes() as $relationField) {
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
        if (! $response->isSuccessfulDocument([200])
            || ! $response->document()->hasAnyPrimaryResources()) {
            return new Collection();
        }

        return $this->mapResourcesToCollection($response->document()->primaryResources(), $relation);
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
                $requestBuilder->addFilter($key, '=', $value);
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
        $requestBuilder->addFilter('uuid', 'IN', $ids);
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
}
