<?php

namespace PhpArsenal\SalesforceMapperBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * Defines a relation between Salesforce objects
 *
 * @Annotation
 */
class Relation extends Annotation
{
    public $field;
    public $class;
    public $name;
}