<?php

namespace PhpArsenal\SalesforceMapperBundle\Annotation;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
class Field
{
    public function __construct(
        public ?string $name = null,
    ) {}
}
