<?php

namespace PhpArsenal\SalesforceMapperBundle\Annotation;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class SObject
{
    public function __construct(
        public ?string $name = null,
        public ?string $discriminatorField = null,
    ) {}
}
