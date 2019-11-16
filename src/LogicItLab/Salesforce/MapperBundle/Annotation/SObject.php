<?php

namespace LogicItLab\Salesforce\MapperBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 */
class SObject extends Annotation
{
    public $name;
}