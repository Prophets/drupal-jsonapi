<?php

namespace Prophets\DrupalJsonApi\Repositories;

use Prophets\DrupalJsonApi\Collection;
use Prophets\DrupalJsonApi\Contracts\BaseRelation;
use Prophets\DrupalJsonApi\Contracts\BaseRepository;
use Prophets\DrupalJsonApi\Relations\HasManyDirty;
use Prophets\DrupalJsonApi\Request\DrupalJsonApiRequestBuilder;
use Prophets\DrupalJsonApi\Model;
use GuzzleHttp\Psr7\Request;
use Http\Adapter\Guzzle6\Client;
use Illuminate\Support\Arr;
use WoohooLabs\Yang\JsonApi\Client\JsonApiClient;
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
                if (($relationResource = $relationship->resource()) !== null) {
                    $model->setRelation(
                        $fieldName,
                        $this->mapResourceToModel(
                            $model->$fieldName()->getNewModel(),
                            $relationResource
                        )
                    );
                }
            }
        }

        return $model;
    }

    /**
     * Map primary resource from the JsonApi response document to our model.
     * @param Document $document
     * @return Model
     */
    protected function mapPrimaryResourceToModel(Document $document)
    {
        $model = $this->getNewModel();

        return $this->mapResourceToModel($model, $document->primaryResource());
    }

    /**
     * Map primary resources from the JsonApi response document to models defined in the map list.
     * @param Document $document
     * @param array $map
     * @return Collection
     */
    protected function mapPrimaryResourcesToCollection(Document $document, array $map = [])
    {
        $collection = new Collection();

        if (! empty($map)) {
            $model = function ($primaryResource) use ($map) {
                $modelClass = Arr::first($map, function ($model) use ($primaryResource) {
                    return $model::getResourceName() == $primaryResource->type();
                });

                if (! $modelClass) {
                    return $this->getNewModel();
                }
                return new $modelClass;
            };
        } else {
            $model = $this->getNewModel();
        }

        foreach ($document->primaryResources() as $primaryResource) {
            if (is_callable($model)) {
                $model = $model($primaryResource);
            }
            $collection[] = $this->mapResourceToModel($model, $primaryResource);
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
                $relationModel = $relation->getNewModel();

                if (! isset($fields[$relationModel->getResourceName()])) {
                    $fields = array_merge_recursive($fields, $this->getResourceFields($relationModel, $relationField));
                }
            }
        }

        return $fields;
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

        if (! $response->isSuccessfulDocument([200])
            || ! $response->document()->hasAnyPrimaryResources()) {
            return null;
        }

        return $this->mapPrimaryResourceToModel($response->document());
    }

    /**
     * @inheritdoc
     */
    public function findForRelation(BaseRelation $relation)
    {
        $response = $this->executeRequest($this->newRelationRequestBuilder($relation));

        if (! $response->isSuccessfulDocument([200])
            || ! $response->document()->hasAnyPrimaryResources()) {
            return null;
        }

        return $this->mapPrimaryResourceToModel($response->document());
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

        if (! $response->isSuccessfulDocument([200])
            || ! $response->document()->hasAnyPrimaryResources()) {
            return null;
        }

        return $this->mapPrimaryResourceToModel($response->document());
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

        if (! $response->isSuccessfulDocument([200])
            || ! $response->document()->hasAnyPrimaryResources()) {
            return null;
        }

        return $this->mapPrimaryResourcesToCollection($response->document());
    }

    /**
     * @inheritdoc
     */
    public function getByIds(array $ids)
    {
        $requestBuilder = $this->newRequestBuilder();
        $requestBuilder->addFilter('uuid', 'IN', $ids);
        $response = $this->executeRequest($requestBuilder);

        if (! $response->isSuccessfulDocument([200])
            || ! $response->document()->hasAnyPrimaryResources()) {
            return null;
        }

        return $this->mapPrimaryResourcesToCollection($response->document());
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
            return $this->mapPrimaryResourcesToCollection($response->document(), $relation->getClassList());
        }
        return $this->mapPrimaryResourcesToCollection($response->document());
    }

    /**
     * @return bool
     */
    public function clearCache()
    {
        return true;
    }
}
