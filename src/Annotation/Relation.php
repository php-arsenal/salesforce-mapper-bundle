<?php

namespace PhpArsenal\SalesforceMapperBundle\Annotation;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Relation
{
    public function __construct(
        public ?string $field = null,
        public ?string $class = null,
        public ?string $name = null,
        public ?bool $optional = null,
    ) {}
}
