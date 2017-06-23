<?php

namespace Prophets\DrupalJsonApi;

use ArrayAccess;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;

class Model implements ArrayAccess
{
    use Models\Concerns\HasAttributes;
    use Models\Concerns\HasRelationships;
    use Models\Concerns\HasExtendFields;
    use HasTimestamps;

    /**
     * @var string
     */
    const CREATED_AT = 'created';

    /**
     * @var string
     */
    const UPDATED_AT = 'changed';

    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected static $resourceName = '';

    /**
     * List of the resource's field to be collected from the repository.
     * @var array
     */
    protected $fields = [];

    /**
     * List of fields to extend on a parent resource.
     * @var array
     */
    protected $extendFields = [];

    /**
     * List of relationships to include in the resource.
     * @var array
     */
    protected $includes = [];

    /**
     * Model constructor.
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->extendFields();
        $this->setDateFormat('Y-m-d');
        $this->timezone = config('drupal-jsonapi.timezone');
        $this->populate($data);
    }

    /**
     * @return string
     */
    public static function getResourceName(): string
    {
        if (empty(static::$resourceName)) {
            throw new \RuntimeException('Resource name was not set.');
        }

        return static::$resourceName;
    }

    /**
     * Get the resource fields.
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Get the resource included relationships.
     * @return array
     */
    public function getIncludes()
    {
        return $this->includes;
    }

    /**
     * Set multiple attributes from array
     *
     * @param array $data
     */
    public function populate(array $data)
    {
        foreach ($data as $key => $value) {
            $this->setAttribute($key, $value);
        }
    }

    /**
     * @return bool
     */
    public function getIncrementing()
    {
        return false;
    }

    /**
     * Dynamically retrieve attributes on the model.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Dynamically set attributes on the model.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Determine if an attribute or relation exists on the model.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return ! is_null($this->getAttribute($key));
    }

    /**
     * Unset an attribute on the model.
     *
     * @param  string  $key
     * @return void
     */
    public function __unset($key)
    {
        unset($this->attributes[$key]);
    }

    /**
     * Whether a offset exists
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->$offset);
    }

    /**
     * Offset to retrieve
     *
     * @param string $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    /**
     * Offset to set
     *
     * @param string $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }

    /**
     * Offset to unset
     *
     * @param string $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->$offset);
    }

    /**
     * @param string $id
     */
    public function setId(string $id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        if (empty($this->id)) {
            throw new \RuntimeException('Trying to get uuid from a resource that is not loaded yet.');
        }
        return $this->id;
    }
}
