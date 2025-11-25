<?php

namespace PhpArsenal\SalesforceMapperBundle\Annotation;

use Doctrine\Common\Collections\ArrayCollection;
use ReflectionClass;

/**
 * reads salesforce attributes using php 8 native reflection
 */
class AnnotationReader
{
    public function getSalesforceFields($modelClass): ArrayCollection
    {
        $properties = $this->getSalesforceProperties($modelClass);
        return new ArrayCollection($properties['fields']);
    }

    public function getSalesforceField($model, $field): ?Field
    {
        $properties = $this->getSalesforceProperties($model);
        return $properties['fields'][$field] ?? null;
    }

    public function getSalesforceRelations($model): array
    {
        $properties = $this->getSalesforceProperties($model);
        return $properties['relations'];
    }

    public function getSalesforceObject($model): ?SObject
    {
        $properties = $this->getSalesforceProperties($model);
        return $properties['object'];
    }

    public function getSalesforceProperties($modelClass): array
    {
        $reflClass = new ReflectionClass($modelClass);
        return $this->getSalesforcePropertiesFromReflectionClass($reflClass);
    }

    protected function getSalesforcePropertiesFromReflectionClass(ReflectionClass $reflClass): array
    {
        $salesforceProperties = [
            'object' => null,
            'relations' => [],
            'fields' => [],
        ];

        $classAttributes = $reflClass->getAttributes(SObject::class);
        if (!empty($classAttributes)) {
            $salesforceProperties['object'] = $classAttributes[0]->newInstance();
        }

        foreach ($reflClass->getProperties() as $reflProperty) {
            $fieldAttributes = $reflProperty->getAttributes(Field::class);
            foreach ($fieldAttributes as $attr) {
                $salesforceProperties['fields'][$reflProperty->getName()] = $attr->newInstance();
            }

            $relationAttributes = $reflProperty->getAttributes(Relation::class);
            foreach ($relationAttributes as $attr) {
                $salesforceProperties['relations'][$reflProperty->getName()] = $attr->newInstance();
            }
        }

        foreach ($reflClass->getMethods() as $reflMethod) {
            $methodAttributes = $reflMethod->getAttributes(Field::class);
            foreach ($methodAttributes as $attr) {
                $field = $attr->newInstance();
                if ($field->name) {
                    $salesforceProperties['fields'][$field->name] = $field;
                }
            }
        }

        if ($reflClass->getParentClass()) {
            $parentProperties = $this->getSalesforcePropertiesFromReflectionClass($reflClass->getParentClass());

            $salesforceProperties['object'] = $salesforceProperties['object'] ?? $parentProperties['object'];
            $salesforceProperties['fields'] = array_merge($parentProperties['fields'], $salesforceProperties['fields']);
            $salesforceProperties['relations'] = array_merge($parentProperties['relations'], $salesforceProperties['relations']);
        }

        return $salesforceProperties;
    }
}
