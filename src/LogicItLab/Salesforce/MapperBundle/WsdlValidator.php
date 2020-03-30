<?php

namespace LogicItLab\Salesforce\MapperBundle;

use LogicItLab\Salesforce\MapperBundle\Annotation\AnnotationReader;
use Symfony\Component\Finder\Finder;

class WsdlValidator
{
    /** @var AnnotationReader */
    private $annotationReader;

    /** @var string */
    private $baseProjectDir;

    /** @var string */
    private $modelDirPath;

    /** @var string */
    private $wsdlPath;

    /** @var string */
    private $wsdlContents;

    /**
     * @param AnnotationReader $annotationReader
     * @param string $baseProjectDir
     * @param string $modelDirPath
     * @param string $wsdlPath
     * @codeCoverageIgnore
     */
    public function __construct(AnnotationReader $annotationReader, string $baseProjectDir, string $modelDirPath, string $wsdlPath)
    {
        $this->annotationReader = $annotationReader;
        $this->baseProjectDir = $baseProjectDir;
        $this->modelDirPath = $modelDirPath;
        $this->wsdlPath = $wsdlPath;
    }

    public function validate(): array
    {
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
        $AllFiles = Finder::create()->files()->in($this->baseProjectDir . $this->modelDirPath)->name('*.php');
        foreach ($AllFiles as $file) {
            $classNames[] = $this->getClassNameFromFile($file);
        }

        return $this->filterNonExistentClasses($classNames);
    }

    private function getClassNameFromFile($file): string
    {
        $realPath = $file->getRealpath();
        $fileName = str_replace($this->baseProjectDir, '', $realPath);
        $className = str_replace('.php', '', $fileName);

        if (strpos($this->baseProjectDir, 'test') !== false) {
            $className = "Tests/$className";
        }

        return str_replace('/', '\\', $className);
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

    private function sortByObjectName(array $missingFields)
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