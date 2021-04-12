<?php

namespace PhpArsenal\SalesforceMapperBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 */
class SObject extends Annotation
{
    public $name;
}