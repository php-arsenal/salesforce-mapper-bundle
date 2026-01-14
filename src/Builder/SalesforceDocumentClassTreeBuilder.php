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

        return $this->sortByDependencies($salesforceDocumentClasses);
    }

    /**
     * topological sort: dependencies come before dependents
     */
    private function sortByDependencies(array $classes): array
    {
        $dependencies = [];
        foreach ($classes as $class) {
            $dependencies[$class] = [];
            foreach ($this->annotationReader->getSalesforceRelations($class) as $relation) {
                if (in_array($relation->class, $classes, true)) {
                    $dependencies[$class][] = $relation->class;
                }
            }
        }

        $sorted = [];
        $visited = [];

        $visit = function (string $class) use (&$visit, &$sorted, &$visited, $dependencies): void {
            if (isset($visited[$class])) {
                return;
            }
            $visited[$class] = true;

            foreach ($dependencies[$class] as $dep) {
                $visit($dep);
            }

            $sorted[] = $class;
        };

        foreach ($classes as $class) {
            $visit($class);
        }

        return $sorted;
    }
}
