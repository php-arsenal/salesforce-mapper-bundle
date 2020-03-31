<?php

namespace LogicItLab\Salesforce\MapperBundle;

use LogicItLab\Salesforce\MapperBundle\Annotation\AnnotationReader;
use Symfony\Component\Finder\Finder;

class WsdlValidator
{
    /** @var AnnotationReader */
    private $annotationReader;

    /** @var string|string[] */
    private $modelDirPath;

    /** @var string */
    private $wsdlPath;

    /** @var string */
    private $wsdlContents;

    /**
     * @param AnnotationReader $annotationReader
     * @codeCoverageIgnore
     */
    public function __construct(AnnotationReader $annotationReader)
    {
        $this->annotationReader = $annotationReader;
    }

    /**
     * @param string|string[] $modelDirPath
     * @param string $wsdlPath
     * @return array
     */
    public function validate($modelDirPath, string $wsdlPath): array
    {
        $this->modelDirPath = $modelDirPath;
        $this->wsdlPath = $wsdlPath;
        $missingFields = [];

        foreach ($this->getAllClassAnnotations() as $annotation) {
            $objectName = $annotation['object']->name;
            $fieldNames = $this->getFieldNames($annotation['fields']);

            if (!$objectName || !$fieldNames) {
                continue;
            }

            foreach ($fieldNames as $fieldName) {
                if ($this->hasField($objectName, $fieldName) === false) {
                    $missingFields[$objectName][] = $fieldName;
                }
            }
        }

        return $missingFields;
    }

    private function getObject(string $objectName): ?string
    {
        preg_match("/<complexType name=\"$objectName\">([\w\W]*)<\/complexType>/", $this->getWsdlContents(), $matches);

        return $matches[0] ?? null;
    }

    private function hasField(string $objectName, string $fieldName): bool
    {
        return strpos($this->getObject($objectName), sprintf('<element name="%s"', $fieldName)) !== false;
    }

    private function getWsdlContents(): string
    {
        if (!$this->wsdlContents) {
            $this->wsdlContents = file_get_contents($this->wsdlPath);
        }

        return $this->wsdlContents;
    }

    private function getAllClassAnnotations(): array
    {
        return array_map(function ($className) {
            return $this->annotationReader->getSalesforceProperties($className);
        }, $this->getAllClassNames());
    }

    private function getAllClassNames(): array
    {
        $classNames = [];
        $allFiles = Finder::create()->files()->in($this->modelDirPath)->name('*.php');
        foreach ($allFiles as $file) {
            $namespace = $this->getNamespaceFromFile($file);
            $className = $this->getClassNameFromFile($file);

            $classNames[] = "$namespace\\$className";
        }

        return $this->filterNonExistentClasses($classNames);
    }

    private function getNamespaceFromFile(\SplFileInfo $file): ?string
    {
        $fileText = file_get_contents($file->getRealPath());

        if (preg_match('#^namespace\s+(.+?);$#sm', $fileText, $matches)) {
            return str_replace('/', '\\', $matches[1]);
        }

        return null;
    }

    private function getClassNameFromFile(\SplFileInfo $file): string
    {
        return str_replace('.php', '', $file->getFileName());
    }

    private function filterNonExistentClasses(array $classNames): array
    {
        $classNames = array_filter($classNames, function ($className) {
            return class_exists($className);
        });

        return $classNames;
    }

    private function getFieldNames(?array $mapFieldAnnotation): ?array
    {
        if (!$mapFieldAnnotation) {
            return null;
        }

        $fieldNames = [];
        foreach ($mapFieldAnnotation as $propertyName => $annotation) {
            $fieldNames[] = $annotation->name;
        }

        return $fieldNames;
    }

    private function sortByObjectName(array $missingFields): array
    {
        ksort($missingFields);

        return $missingFields;
    }

    public function buildErrorMessage(array $missingFields): string
    {
        $list = "These objects or fields are missing in wsdl:";
        foreach ($this->sortByObjectName($missingFields) as $objectName => $fields) {
            foreach ($fields as $field) {
                $list .= "\n$objectName -> $field";
            }
        }

        return $list;
    }
}