<?php

namespace PhpArsenal\SalesforceMapperBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 */
class Field extends Annotation
{
    public $name;
}