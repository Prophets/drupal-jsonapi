<?php

namespace Prophets\DrupalJsonApi\Repositories;

use Prophets\DrupalJsonApi\Collection;
use Prophets\DrupalJsonApi\Contracts\BaseRelation;
use Prophets\DrupalJsonApi\Contracts\BaseRepository;
use Prophets\DrupalJsonApi\Relations\HasMany;
use Prophets\DrupalJsonApi\Relations\HasManyDirty;
use Prophets\DrupalJsonApi\Request\DrupalJsonApiRequestBuilder;
use Prophets\DrupalJsonApi\Model;
use GuzzleHttp\Psr7\Request;
use Http\Adapter\Guzzle6\Client;
use Illuminate\Support\Arr;
use WoohooLabs\Yang\JsonApi\Client\JsonApiClient;
use WoohooLabs\Yang\JsonApi\Response\JsonApiResponse;
use WoohooLabs\Yang\JsonApi\Schema\Document;
use WoohooLabs\Yang\JsonApi\Schema\ResourceObject;

class JsonApiBaseRepository implements BaseRepository
{
    use Concerns\HasGlobalScopes;

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
     * @param Model $model
     * @param null|string $apiBaseUrl
     */
    public function __construct(Model $model, $apiBaseUrl = null)
    {
        $this->model = $model;
        $this->apiBaseUrl = $apiBaseUrl ?: config('drupal-jsonapi.base_url');
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
        $resourceFields = [];

        if ($relation instanceof HasManyDirty) {
            foreach ($relation->getClassList() as $modelClass) {
                $model = new $modelClass;

                if (! $model instanceof Model) {
                    throw new \InvalidArgumentException('Model class must be instance of ' . Model::class);
                }
                $resourceFields = array_merge_recursive($resourceFields, $this->getResourceFields($model));
            }
        } else {
            $resourceFields = $this->getResourceFields($this->model);
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
        $client = Client::createWithConfig([]);
        $jsonApiClient = new JsonApiClient($client);
        $request = $requestBuilder->getRequestWithScopes();

        return $jsonApiClient->sendRequest($request);
    }

    /**
     * Map the JsonApi ResourceObject to an application model.
     * @param Model $model
     * @param ResourceObject $resource
     * @return Model
     */
    protected function mapResourceToModel(Model $model, ResourceObject $resource)
    {
        $model->setRawAttributes($resource->attributes(), true);
        $model->setId($resource->id());

        foreach ($model->getFields() as $fieldName) {
            if (($relationship = $resource->relationship($fieldName)) !== null) {
                $relationResource = null;
                $resourceMap = $relationship->resourceMap();

                if (count($resourceMap) > 0) {
                    $modelRelation = $model->$fieldName();

                    if ($relationship->isToManyRelationship() && $modelRelation instanceof HasMany) {
                        $model->setRelation(
                            $fieldName,
                            $this->mapResourcesToCollection(
                                $relationship->resources(),
                                $modelRelation instanceof HasManyDirty ? $modelRelation->getClassList() : []
                            )
                        );
                    } else {
                        $model->setRelation(
                            $fieldName,
                            $this->mapResourceToModel($modelRelation->getNewModel(), $relationship->resource())
                        );
                    }
                }
            }
        }

        return $model;
    }

    /**
     * Map primary resources from the JsonApi response document to models defined in the map list.
     * @param array $resources
     * @param array $map
     * @return Collection
     */
   protected function mapResourcesToCollection(array $resources, array $map = [])
    {
        $collection = new Collection();

        if (! empty($map)) {
            $model = function ($resource) use ($map) {
                $modelClass = Arr::first($map, function ($model) use ($resource) {
                    return $model::getResourceName() == $resource->type();
                });

                if (! $modelClass) {
                    return $this->getNewModel();
                }
                return new $modelClass;
            };
        } else {
            $model = $this->model;
        }

        foreach ($resources as $resource) {
            if (is_callable($model)) {
                $newModel = $model($resource);
            } else {
                $newModel = new $model;
            }
            $collection[] = $this->mapResourceToModel($newModel, $resource);
        }
        return $collection;
    }

    /**
     * Retrieve 'fields' and 'includes' for a model and it's relations.
     * @param Model $model
     * @param null $field
     * @return array
     */
    protected function getResourceFields(Model $model, $field = null)
    {
        $fields = [
            'fields' => [
                $model->getResourceName() => array_unique(array_merge(
                    $model->getFields(),
                    $model->getIncludes()
                )),
            ],
            'includes' => array_map(function ($value) use ($field) {
                return $field ? $field . '.' . $value : $value;
            }, $model->getIncludes())
        ];

        foreach ($model->getIncludes() as $relationField) {
            if (! method_exists($model, $relationField)) {
                continue;
            }
            $relation = $model->$relationField();

            if ($relation instanceof BaseRelation) {
                $relationModels = $relation instanceof HasManyDirty ? array_map(function ($modelClass) {
                    return new $modelClass;
                }, $relation->getClassList()) : [$relation->getNewModel()];

                foreach ($relationModels as $relationModel) {
                    if (! isset($fields['fields'][$relationModel->getResourceName()])) {
                        $fields = array_merge_recursive(
                            $fields,
                            $this->getResourceFields($relationModel, $relationField)
                        );
                    }
                }
            }
        }

        return $fields;
    }

    /**
     * Map primary resource from the JsonApi response to our model.
     * @param JsonApiResponse $response
     * @return Model
     */
    protected function responseToModel(JsonApiResponse $response)
    {
        if (! $response->isSuccessfulDocument([200])
            || ! $response->document()->hasAnyPrimaryResources()) {
            return null;
        }
        $model = $this->getNewModel();

        return $this->mapResourceToModel($model, $response->document()->primaryResource());
    }

    /**
     * Map primary resource from the JsonApi response to a collection of models.
     * @param JsonApiResponse $response
     * @param array $map
     * @return Collection
     */
    protected function responseToCollection(JsonApiResponse $response, array $map = [])
    {
        if (! $response->isSuccessfulDocument([200])
            || ! $response->document()->hasAnyPrimaryResources()) {
            return new Collection();
        }

        return $this->mapResourcesToCollection($response->document()->primaryResources(), $map);
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
            $requestBuilder->addSimpleFilter($key, $value);
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

        return $this->responseToModel($response);
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

        if (! $response->isSuccessfulDocument([200])
            || ! $response->document()->hasAnyPrimaryResources()) {
            return null;
        }

        if ($relation instanceof HasManyDirty) {
            return $this->responseToCollection($response, $relation->getClassList());
        }
        return $this->responseToCollection($response);
    }

    /**
     * @return bool
     */
    public function clearCache()
    {
        return true;
    }
}
