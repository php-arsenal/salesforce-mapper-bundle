<?php

namespace PhpArsenal\SalesforceMapperBundle\Response;

use Countable;
use Iterator;
use PhpArsenal\SalesforceMapperBundle\Mapper;
use OuterIterator;
use PhpArsenal\SoapClient\Result\RecordIterator;

/**
 * A mapped record iterator encapsulates a plain Salesforce record iterator and
 * returns a mapped domain model for each Salesforce record
 */
class MappedRecordIterator implements OuterIterator, Countable
{
    /**
     * Record iterator
     *
     * @var RecordIterator
     */
    protected $recordIterator;

    /**
     * Mapper
     *
     * @var Mapper
     */
    protected $mapper;

    /**
     * Domain model object
     *
     * @var mixed
     */
    protected $modelClass;

    /**
     * Construct a mapped record iterator
     *
     * @param RecordIterator $recordIterator
     * @param Mapper              Salesforce mapper
     * @param mixed $modelClass Model class name
     */
    public function __construct(RecordIterator $recordIterator, Mapper $mapper, $modelClass)
    {
        $this->recordIterator = $recordIterator;
        $this->mapper = $mapper;
        $this->modelClass = $modelClass;
    }

    /**
     * {@inheritdoc}
     */
    public function getInnerIterator(): ?Iterator
    {
        return $this->recordIterator;
    }

    /**
     * Get domain model object
     *
     * @return mixed The domain model object containing the values from the
     *               Salesforce record
     */
    public function current(): mixed
    {
        return $this->get($this->key());
    }

    /**
     * {@inheritdoc}
     */
    public function next(): void
    {
        $this->recordIterator->next();
    }

    /**
     * {@inheritdoc}
     */
    public function key(): mixed
    {
        return $this->recordIterator->key();
    }

    /**
     * {@inheritdoc}
     */
    public function valid(): bool
    {
        return $this->recordIterator->valid();
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        $this->recordIterator->rewind();
    }

    /**
     * Get first domain model object in collection
     *
     * @return mixed
     */
    public function first()
    {
        return $this->get(0);
    }

    /**
     * Get total number of records returned by Salesforce
     */
    public function count(): int
    {
        return $this->recordIterator->count();
    }

    /**
     * Get object at key
     *
     * @param int $key
     * @return object|null
     */
    public function get($key)
    {
        $this->recordIterator->seek($key);
        $sObject = $this->recordIterator->current();
        if (!$sObject) {
            return null;
        }

        return $this->mapper->mapToDomainObject($sObject, $this->modelClass);
    }
}