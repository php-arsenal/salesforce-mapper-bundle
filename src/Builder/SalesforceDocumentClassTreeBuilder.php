<?php

namespace PhpArsenal\SalesforceMapperBundle\Builder;

use PhpArsenal\SalesforceMapperBundle\Annotation\AnnotationReader;

class SalesforceDocumentClassTreeBuilder
{
    public function __construct(
        private AnnotationReader $annotationReader,
        private array $documentClasses
    ) {
    }

    public function build(): array
    {
        $salesforceDocumentClasses = [];

        foreach ($this->documentClasses as $documentClass) {
            if ($this->annotationReader->getSalesforceObject($documentClass)) {
                    $salesforceDocumentClasses[] = $documentClass;
            }
        }

        asort($salesforceDocumentClasses);

        return array_values($salesforceDocumentClasses);
    }
}
