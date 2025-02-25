<?php

namespace PhpArsenal\SalesforceMapperBundle;

use DateTime;
use DateTimeZone;
use Doctrine\Common\Cache\Cache;
use Doctrine\ODM\MongoDB\Mapping\Annotations\DiscriminatorMap;
use InvalidArgumentException;
use PhpArsenal\SalesforceMapperBundle\Annotation;
use PhpArsenal\SalesforceMapperBundle\Annotation as Salesforce;
use PhpArsenal\SalesforceMapperBundle\Annotation\AnnotationReader;
use PhpArsenal\SalesforceMapperBundle\Event\BeforeSaveEvent;
use PhpArsenal\SalesforceMapperBundle\Query\Builder;
use PhpArsenal\SalesforceMapperBundle\Response\MappedRecordIterator;
use PhpArsenal\SoapClient\ClientInterface;
use PhpArsenal\SoapClient\Result;
use ProxyManager\Configuration;
use ReflectionClass;
use ReflectionObject;
use stdClass;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Traversable;
use UnexpectedValueException;

/**
 * This mapper makes interaction with the Salesforce API using full objects
 * much easier
 *
 * Working with the mapper requires you to annotate your objects. A set of
 * standard objects is included in the Model directory. If you need access
 * to custom properties on these objects, it is recommended to
 * extend the standard objects, add the properties and annotate them
 * (using @Salesforce\Field annotations). If you want this mapper to accept
 * completely custom objects, you can extend from Model/AbstractModel, and add
 * a @Salesforce\SObject annotation.
 */
class Mapper
{
    /**
     * Salesforce client
     *
     * @var ClientInterface
     */
    private $client;

    /**
     * Salesforce annotations reader
     *
     * @var AnnotationReader
     */
    private $annotationReader;

    /**
     * Cache
     *
     * @var Cache
     */
    private $cache;

    /**
     * Symfony event dispatcher
     *
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    protected $unitOfWork;

    protected $objectDescriptions = array();

    /**
     * Construct mapper
     *
     * @param SoapClient $soapClient
     * @param AnnotationReader $annotationReader
     * @param Cache $cache
     */
    public function __construct(
        ClientInterface $client,
        AnnotationReader $annotationReader,
        Cache $cache,
        private array $salesforceDocumentClasses
    )
    {
        $this->client = $client;
        $this->annotationReader = $annotationReader;
        $this->cache = $cache;
        $this->unitOfWork = new UnitOfWork($this, $this->annotationReader);
    }


    /**
     * Get event dispatcher
     *
     * @return type EventDispatcherInterface
     */
    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }

    /**
     * Set event dispatcher
     *
     * @param EventDispatcherInterface $eventDispatcher
     * @return Mapper
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
        return $this;
    }

    /**
     * Get object count
     *
     * @param string $modelClass Model class name
     * @param boolean $includeDeleted
     * @param array $criteria
     * @return int
     */
    public function count($modelClass, $includeDeleted = false, array $criteria = array())
    {
        $object = $this->annotationReader->getSalesforceObject($modelClass);
        if (null === $object) {
            throw new UnexpectedValueException('Model has no Salesforce annotation');
        }

        $query = trim("select count() from {$object->name} "
            . $this->getQueryWherePart($criteria, $modelClass));

        if (true === $includeDeleted) {
            $result = $this->client->queryAll($query);
        } else {
            $result = $this->client->query($query);
        }

        if ($result) {
            return $result->count();
        }
    }

    /**
     * Delete one or more records from Salesforce
     *
     * @param array|Traversable $models
     * @return array
     */
    public function delete($models)
    {
        if (!is_array($models) && !($models instanceof Traversable)) {
            throw new InvalidArgumentException('$models must be iterable');
        }

        $ids = array();
        foreach ($models as $model) {
            $ids[] = $model->getId();
        }

        return $this->client->delete($ids);
    }

    /**
     * Find one object by id
     *
     * @param mixed $model Model object or class name
     * @param string $id Object id
     * @param int $related Number of levels of related records to include
     * @return object       Mapped domain object
     */
    public function find($model, $id, $related = 1)
    {
        $query = $this->getQuerySelectPart($model, $related)
            . sprintf(' where Id=\'%s\'', $id);

        $result = $this->client->query($query);
        $mappedRecordIterator = new MappedRecordIterator($result, $this, $model);

        return $mappedRecordIterator->first();
    }

    /**
     * Find multiple objects by criteria and return result as an iterator
     *
     * @param object $model Model object or class name
     * @param array $criteria Criteria as key/value pairs
     * @param array $order Order to sort by as key/value pairs
     * @param int $related Number of levels of related records to include
     * @param bool $deleted Whether to include deleted records
     * @param bool $returnAsArray Whether to return as array
     * @return MappedRecordIterator
     */
    public function findBy(
        $model,
        array $criteria,
        array $order = array(),
        $related = 1,
        $deleted = false,
        $returnAsArray = false
    ) {
        $query = $this->getQuerySelectPart($model, $related)
            . $this->getQueryWherePart($criteria, $model)
            . $this->getQueryOrderByPart($order, $model);

        if (true === $deleted) {
            $result = $this->client->queryAll($query);
        } else {
            $result = $this->client->query($query);
        }

        if ($returnAsArray) {
            return iterator_to_array(new MappedRecordIterator($result, $this, $model));
        }
        return new MappedRecordIterator($result, $this, $model);
    }

    /**
     * Find one object by criteria
     *
     * @param object $model
     * @param array $criteria
     * @param array $order
     * @param int $related
     * @param bool $deleted
     * @return object
     */
    public function findOneBy(
        $model,
        array $criteria,
        array $order = array(),
        $related = 2,
        $deleted = false
    ) {
        $iterator = $this->findBy($model, $criteria, $order, $related, $deleted);
        return $iterator->first();
    }

    /**
     * Fetch all objects of a certain type
     *
     * @param object $model Model object or class name
     * @param array $order Order to sort by as key/value pairs
     * @param boolean $related Number of levels of related records to include
     * @param boolean $deleted Whether to include deleted records
     * @return MappedRecordIterator
     */
    public function findAll(
        $model,
        array $order = array(),
        $related = 1,
        $deleted = false
    ) {
        return $this->findBy($model, array(), $order, $related, $deleted);
    }

    /**
     * Get object description, if possible from cache
     *
     * @param object $model Model object or class name
     * @return Response\DescribeSObjectResult
     * @throws InvalidArgumentException
     */
    public function getObjectDescription($model)
    {
        $object = $this->annotationReader->getSalesforceObject($model);

        if (!isset($this->objectDescriptions[$object->name])) {
            $this->objectDescriptions[$object->name] =
                $this->doGetObjectDescription($object->name);
        }

        return $this->objectDescriptions[$object->name];
    }

    /**
     * Save one or more domain models to Salesforce
     *
     * @param mixed $model One model or array of models
     * @return Result\SaveResult[]
     */
    public function save($model)
    {
        if (is_array($model)) {
            $models = $model;
        } elseif ($model instanceof Traversable) {
            $models = array();
            foreach ($model as $m) {
                $models[] = $m;
            }
        } else {
            $models = array($model);
        }

        if ($this->eventDispatcher) {
            $event = new BeforeSaveEvent($models);
            $this->eventDispatcher->dispatch($event, Events::beforeSave);
        }

        $objectsToBeCreated = array();
        $objectsToBeUpdated = array();
        $modelsWithoutId = array();

        foreach ($models as $model) {
            $object = $this->annotationReader->getSalesforceObject($model);
            $sObject = $this->mapToSalesforceObject($model);
            if (isset($sObject->Id) && null !== $sObject->Id) {
                $objectsToBeUpdated[$object->name][] = $sObject;
            } else {
                $objectsToBeCreated[$object->name][] = $sObject;
                $modelsWithoutId[$object->name][] = $model;
            }
        }

        $results = array();
        foreach ($objectsToBeCreated as $objectName => $sObjects) {
            $reflClass = new ReflectionClass(current(
                $modelsWithoutId[$objectName]
            ));
            $reflProperty = $reflClass->getProperty('id');
            $reflProperty->setAccessible(true);

            $saveResults = $this->client->create($sObjects, $objectName) ?? [];
            for ($i = 0; $i < count($saveResults); $i++) {
                $newId = $saveResults[$i]->getId();
                $model = $modelsWithoutId[$objectName][$i];
                $reflProperty->setValue($model, $newId);
            }

            $results['created'] = $saveResults;
        }

        foreach ($objectsToBeUpdated as $objectName => $sObjects) {
            $results['updated'] = $this->client->update($sObjects, $objectName);
        }

        return $results;
    }

    /**
     * Map a Salesforce object to a domain model object
     *
     * Uses reflection instead of setters because read-only properties on
     * ojects should not need a setter.
     *
     * @param object $sObject
     * @param string $modelClass Model class name
     * @return object A mapped instantiation of the model class
     */
    public function mapToDomainObject($sObject, $modelClass)
    {
        $reflClass = new ReflectionClass($modelClass);
        if($reflClass->isAbstract()) {
            $discriminatorMap = $this->annotationReader->reader->getClassAnnotation($reflClass, DiscriminatorMap::class);
            $discriminatorField = $this->annotationReader->getSalesforceObject($modelClass)->discriminatorField;
            $modelClass = $discriminatorMap->value[$sObject->{$discriminatorField}];
            $reflClass = new ReflectionClass($modelClass);
        }

        if ($this->unitOfWork->find($modelClass, $sObject->Id)) {
            $model = $this->unitOfWork->find($modelClass, $sObject->Id);
        }
        else {
            $model = $reflClass->newInstanceWithoutConstructor();
        }

        $reflObject = new ReflectionObject($model);
        $fields = $this->annotationReader->getSalesforceFields($modelClass);
        foreach ($fields as $name => $field) {
            if (isset($sObject->{$field->name}) && $reflObject->hasProperty($name)) {
                // Use reflection to set the protected/private properties
                $reflProperty = $reflObject->getProperty($name);
                $reflProperty->setAccessible(true);
                $reflProperty->setValue($model, $sObject->{$field->name});
            }
        }

        // Set Salesforce relations on domain object
        $relations = $this->annotationReader->getSalesforceRelations($modelClass);
        foreach ($relations as $property => $relation) {

            // Relation name must be set
            if (isset($sObject->{$relation->name})) {
                $value = $sObject->{$relation->name};
                if ($value instanceof Result\RecordIterator) {
                    $value = new MappedRecordIterator(
                        $value, $this, $relation->class
                    );
                } else {
                    $value = $this->mapToDomainObject(
                        $sObject->{$relation->name}, $relation->class
                    );
                }

                $reflProperty = $reflObject->getProperty($property);
                $reflProperty->setAccessible(true);
                $reflProperty->setValue($model, $value);
            }
        }

        // Add mapped model to unit of work
        $this->unitOfWork->addToIdentityMap($model);

        return $model;
    }

    /**
     * Map a PHP model object to a Salesforce object
     *
     * The PHP object must be properly annoated
     *
     * @param mixed $model PHP model object
     * @return stdClass
     */
    public function mapToSalesforceObject($model)
    {
        $sObject = new stdClass;
        $sObject->fieldsToNull = array();

        /** @var Result\DescribeSObjectResult $objectDescription */
        $objectDescription = $this->getObjectDescription($model);
        $reflClass = new ReflectionClass((new Configuration())->getClassNameInflector()->getUserClassName($model::class));
        $mappedProperties = $this->annotationReader->getSalesforceFields($model);
        $mappedRelations = $this->annotationReader->getSalesforceRelations($model);
        $allMappings = $mappedProperties->toArray() + $mappedRelations;

        foreach ($allMappings as $property => $mapping) {
            $value = null;
            if ($mapping instanceof Annotation\Field) {
                $fieldDescription = $objectDescription->getField($mapping->name);
                $fieldName = $mapping->name;
            } elseif ($mapping instanceof Annotation\Relation
                && $mapping->field) {
                // Only one-to-one and one-to-many relations will be saved
                $fieldDescription = $objectDescription->getField($mapping->field);
                $fieldName = $mapping->field;
            } else {
                // Do not save many-to-many relations
                continue;
            }

            if (!$fieldDescription) {
                throw new InvalidArgumentException(sprintf(
                    'Field %s (for property ‘%s’) does not exist on %s. '
                    . 'If you think it does, try emptying your cache.',
                    $fieldName, $property, $objectDescription->getName()
                ));
            }

            // If the object is created, only allow creatable fields.
            // If the object is updated, only allow updatable.
            if (($model->getId() && $fieldDescription->isUpdateable())
                || (!$model->getId() && $fieldDescription->isCreateable())
                // for 'Id' field:
                || $fieldDescription->isIdLookup()) {

                // Get value through reflection
                if($reflClass->hasProperty($property)) {
                    $reflProperty = $reflClass->getProperty($property);
                    $reflProperty->setAccessible(true);
                    if($reflProperty->isInitialized($model)) {
                        $value = $reflProperty->getValue($model);
                    }
                }
                else {
                    // Get from method
                    foreach($reflClass->getMethods() as $reflectionMethod) {
                        /** @var Annotation\Field|null $methodFieldAnnotation */
                        $methodFieldAnnotation = $this->annotationReader->reader->getMethodAnnotation($reflectionMethod, Annotation\Field::class);

                        if($methodFieldAnnotation && $methodFieldAnnotation->name === $fieldDescription->getName()) {
                            $value = $reflectionMethod->invoke($model);
                            break;
                        }
                    }
                }

                if ($mapping instanceof Annotation\Relation) {
                    // @todo Implements recursive saving for new related
                    // records, too. This only works for already existing
                    // records.
                    $sObject->{$fieldDescription->getName()} = null;

                    if ($value !== null && method_exists($value, 'getId')) {
                        $sObject->{$fieldDescription->getName()} = $value->getId();
                    }

                    continue;
                }

                if (null === $value || (is_string($value) && $value === '')) {
                    // Do not set fieldsToNull on create
                    if ($model->getId()) {
                        $sObject->fieldsToNull[] = $fieldDescription->getName();
                    }
                } else {
                    $sObject->{$fieldDescription->getName()} = $value;
                }
            }
        }

        // Strip all values from fields to null for which values have been
        // set in the SObject
        if (isset($sObject->fieldsToNull)) {
            foreach ($sObject->fieldsToNull as $fieldToNull) {
                if (isset($sObject->$fieldToNull)) {
                    $key = array_search($fieldToNull, $sObject->fieldsToNull);
                    if ($key !== false) {
                        unset($sObject->fieldsToNull[$key]);
                    }
                }
            }
        }

        return $sObject;
    }

    /**
     * Get object description for Salesforce object
     *
     * @param string $objectName Name of the Salesforce object
     * @return DescribeSObjectResult
     * @throws InvalidArgumentException
     */
    private function doGetObjectDescription($objectName)
    {
        $cacheId = sprintf('salesforce_mapper.object_description.%s',
            $objectName);
        if ($this->cache->contains($cacheId)) {
            return $this->cache->fetch($cacheId);
        }

        $descriptions = $this->client->describeSObjects(array($objectName));
        if (count($descriptions) === 0) {
            throw new InvalidArgumentException('Salesforce object does not exist');
        }

        $description = /* @var $description DescribeSObjectResult */
            $descriptions[0];
        $this->cache->save($cacheId, $description);
        return $description;
    }


    /**
     * Get query basis
     *
     * @param string $modelClass Model class name
     * @param int $related Number of levels of related records to include
     *                           in query
     *                           0: do not include related records
     *                           1: include one level of related records, for
     *                              instance owner on opportunity
     *                           2: include two levels, for instance owner and
     *                              account owner on opportunity.
     * @return string
     */
    private function getQuerySelectPart($modelClass, $related)
    {
        $object = $this->annotationReader->getSalesforceObject($modelClass);
        $fields = $this->getFields($modelClass, $related);
        $oneToMany = $this->getOneToManySubqueries($modelClass, $related);

        $select = $this->getSelect($object->name, $fields, $oneToMany);
        return $select;
    }

    private function getSelect($object, $fields, $subqueries = array())
    {
        $select = 'select '
            . implode(',', $fields);
        if (count($subqueries) > 0) {
            $select .= ', ' . implode(',', $subqueries);
        }

        $select .= ' from ' . $object;
        return $select;
    }

    /**
     * Get SOQL where query part based on criteria array
     *
     * @param array $criteria
     * @return string
     */
    private function getQueryWherePart(array $criteria, $model)
    {
        $whereParts = array();
        $object = $this->annotationReader->getSalesforceObject($model);
        $fields = $this->annotationReader->getSalesforceFields($model);
        $objectDescription = $this->doGetObjectDescription($object->name);

        foreach ($criteria as $key => $value) {

            // Check if the criterion has an operator
            $keyParts = explode(' ', $key);

            // Criterion key has an operator part
            if (isset($keyParts[1])) {
                $operator = $keyParts[1];
            } else {
                // Criterion key has no operator, so add it ourselves
                $operator = '=';
            }

            $name = $keyParts[0];
            $field = $this->annotationReader->getSalesforceField($model, $name);
            if (!$field) {
                throw new InvalidArgumentException('Invalid field ' . $name);
            }

            if (is_array($value)) {
                $quotedValueList = array();

                foreach ($value as $v) {
                    $quotedValueList[] = $this->getQuotedWhereValue($field, $v, $objectDescription);
                }

                $quotedValue = '(' . implode(',', $quotedValueList) . ')';
            } else {
                $quotedValue = $this->getQuotedWhereValue($field, $value, $objectDescription);
            }

            $whereParts[] = sprintf('%s %s %s',
                $field->name,
                $operator,
                $quotedValue
            );
        }

        if (!empty($whereParts)) {
            return ' where ' . implode(' and ', $whereParts);
        }
    }

    /**
     * Get quoted where value
     *
     * @param Annotation\Field $field
     * @param mixed $value
     * @param DescribeSObjectResult $description
     * @return string
     * @throws InvalidArgumentException
     * @link http://www.salesforce.com/us/developer/docs/api/Content/field_types.htm#topic-title
     */
    private function getQuotedWhereValue(
        Annotation\Field $field,
                         $value,
        Result\DescribeSObjectResult $description
    ) {
        $fieldDescription = $description->getField($field->name);
        if (!$fieldDescription) {
            throw new InvalidArgumentException(
                sprintf('\'%s\' on object %s is not a valid field',
                    $field->name,
                    $description->getName()
                )
            );
        }

        switch ($fieldDescription->getType()) {
            case 'date':
                if ($value instanceof DateTime) {
                    return $value->format('Y-m-d');
                }
            case 'datetime':
                if ($value instanceof DateTime) {
                    $value = $value->setTimeZone(new DateTimeZone('UTC'));
                    return $value->format('Y-m-d\TH:i:sP');
                } elseif (null != $value) {
                    // A text representation, such as ‘yesterday’: these should
                    // not be enclosed in quotes
                    return $value;
                } else {
                    return 'null';
                }
            case 'boolean':
                return $value ? 'true' : 'false';
            case 'double':
            case 'currency':
            case 'percent':
            case 'int':
                return $value;
            default:
                return "'" . addslashes($value) . "'";
        }
    }

    /**
     * Get SOQL order by query part from order by array
     *
     * @param array $orderBy
     * @return string
     */
    private function getQueryOrderByPart(array $orderBy, $model)
    {
        $orderParts = array();
        foreach ($orderBy as $field => $direction) {
            $fieldAnnotation = $this->annotationReader->getSalesforceField($model, $field);
            $orderParts[] = $fieldAnnotation->name . ' ' . $direction;
        }

        if (!empty($orderParts)) {
            return ' order by ' . implode(',', $orderParts);
        }
    }

    /**
     * Get Salesforce fields and its relations from a Salesforce-annotated model
     *
     * @param string $modelClass
     * @param int $includeRelatedLevels
     * @param string $ignoreObject Salesforce object name of model for which
     *                              fields should not be returned
     * @return array
     */
    public function getFields($modelClass, $includeRelatedLevels, $ignoreObject = null)
    {
        $modelClasses = [$modelClass];

        if((new ReflectionClass($modelClass))->isAbstract()) {
            foreach ($this->salesforceDocumentClasses as $salesforceDocumentClass) {
                if (is_subclass_of($salesforceDocumentClass, $modelClass)) {
                    $modelClasses[] = $salesforceDocumentClass;
                }
            }
        }

        unset($modelClass);
        $fields = array();

        foreach($modelClasses as $modelClass) {
            foreach ($this->annotationReader->getSalesforceFields($modelClass) as $field) {
                $fields[] = $field->name;
            }

            $description = $this->getObjectDescription($modelClass);

            if ($includeRelatedLevels > 0) {
                foreach ($this->annotationReader->getSalesforceRelations($modelClass) as $relation) {
                    // Only process one-to-one and many-to-one relations here;
                    // one-to-many relations must be looked up as subquery.
                    if (!$relation->field) {
                        continue;
                    }

                    // Check whether we can find this relation
                    $relationshipField = $description->getRelationshipField($relation->field);
                    if (!$relationshipField) {
                        throw new InvalidArgumentException(
                            'Field ' . $relation->field . ' does not exist on ' . $description->getName());
                        continue;
                    }

                    // If the referenced object should be ignored, don't fetch its
                    // fields
                    if ($ignoreObject && $relationshipField->references($ignoreObject)) {
                        continue;
                    }

                    $relatedFields = $this->getFields($relation->class, --$includeRelatedLevels);
                    foreach ($relatedFields as $relatedField) {
                        $fields[] = sprintf('%s.%s', $relationshipField->getRelationshipName(), $relatedField);
                    }
                }
            }
        }

        return array_unique($fields);
    }

    /**
     * Gets subqueries (sub selects) for annoted one-to-many relations on the
     * model
     *
     * @param object $model
     * @param int $includeRelatedLevels
     */
    public function getOneToManySubqueries($model, $includeRelatedLevels)
    {
        $relations = $this->annotationReader->getSalesforceRelations($model);
        $object = $this->annotationReader->getSalesforceObject($model);
        $subqueries = array();

        if ($includeRelatedLevels > 0) {
            foreach ($relations as $relation) {
                // Only process one-to-many relations here
                if ($relation->field) {
                    continue;
                }

                $fields = $this->getFields($relation->class, $includeRelatedLevels, $object->name);
                $subqueries[] = sprintf('(%s)',
                    $this->getSelect($relation->name, $fields));
            }
        }

        return $subqueries;
    }

    public function merge($merge)
    {
    }

    /**
     * Create query builder
     *
     * @return Builder
     */
    public function createQueryBuilder()
    {
        return new Builder($this, $this->client, $this->annotationReader);
    }

    /*
     * Get unit of work
     *
     * @return UnitOfWork
     */
    public function getUnitOfWork()
    {
        return $this->unitOfWork;
    }

    /**
     * @return ClientInterface
     */
    public function getClient()
    {
        return $this->client;
    }
}
