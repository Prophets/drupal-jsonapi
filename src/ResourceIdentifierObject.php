<?php

namespace Prophets\DrupalJsonApi;

use http\Exception\InvalidArgumentException;
use Prophets\DrupalJsonApi\Concerns\HasMeta;
use Prophets\DrupalJsonApi\Contracts\ResourceIdentifierObject as ResourceIdentifierObjectContract;
use WoohooLabs\Yang\JsonApi\Schema\ResourceObject;

class ResourceIdentifierObject implements ResourceIdentifierObjectContract
{
    use HasMeta;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $id;

    /**
     * ResourceIdentifierObject constructor.
     *
     * @param $type
     * @param $id
     */
    public function __construct($type, $id)
    {
        if (empty($type)) {
            throw new \InvalidArgumentException('Type cannot be empty.');
        }
        if (empty($id)) {
            throw new \InvalidArgumentException('Id cannot be empty.');
        }
        $this->type = $type;
        $this->id   = $id;
    }

    /**
     * @return string
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * @return Meta
     */
    public function meta(): Meta
    {
        return $this->getMeta();
    }

    /**
     * @param array $resourceLink
     *
     * @return ResourceIdentifierObject
     */
    public static function createFromResourceLink(array $resourceLink)
    {
        return new self($resourceLink['type'], $resourceLink['id']);
    }
}
