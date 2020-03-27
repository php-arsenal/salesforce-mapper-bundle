<?php

namespace LogicItLab\Salesforce\MapperBundle;

use LogicItLab\Salesforce\MapperBundle\Annotation\AnnotationReader;
use LogicItLab\Salesforce\MapperBundle\Exception\FileHandlerNotInitializedException;
use Symfony\Component\Finder\Finder;

class WsdlValidator
{
    /** @var AnnotationReader */
    private $annotationReader;

    /** @var string */
    private $srcPath;

    /** @var string */
    private $relativePath;

    /** @var string */
    private $wsdlPath;

    /**
     * @param AnnotationReader $annotationReader
     * @param string $srcPath
     * @param string $relativePath
     * @param string $wsdlPath
     * @codeCoverageIgnore
     */
    public function __construct(AnnotationReader $annotationReader, string $srcPath, string $relativePath, string $wsdlPath)
    {
        $this->annotationReader = $annotationReader;
        $this->srcPath = $srcPath;
        $this->relativePath = $relativePath;
        $this->wsdlPath = $wsdlPath;
    }

    /**
     * @return array
     * @throws FileHandlerNotInitializedException
     * @throws \ReflectionException
     */
    public function retrieveMissingFields(): array
    {
        $missingFields = [];

        $fileHandler = fopen($this->wsdlPath, "r");
        $this->initializeFileHandler($fileHandler);

        foreach ($this->getAllClassAnnotations() as $annotation) {
            $objectName = $annotation['object']->name;
            $fieldNames = $this->getFieldNames($annotation['fields']);

            if (!$objectName || !$fieldNames) {
                continue;
            }

            if ($this->hasObject($fileHandler, $objectName) === false) {
                $missingFields[$objectName][] = "entire object";
                continue;
            }

            foreach ($fieldNames as $fieldName) {
                if ($this->hasField($fileHandler, $fieldName) === false) {
                    $missingFields[$objectName][] = $fieldName;
                }
            }
        }

        return $missingFields;
    }

    private function getAllClassAnnotations(): array
    {
        $annotations = array_map(function ($className) {
            return $this->annotationReader->getSalesforceProperties($className);
        }, $this->getAllClassNames());

        usort($annotations, function ($a, $b) {
            return strcmp($a['object']->name, $b['object']->name);
        });

        return $annotations;
    }

    private function getAllClassNames(): array
    {
        $classNames = [];
        $AllFiles = Finder::create()->files()->in($this->srcPath.$this->relativePath)->name('*.php');
        foreach ($AllFiles as $file) {
            $classNames[] = $this->getClassNameFromFile($file);
        }

        return $this->sort($this->filterNonExistentClasses($classNames));
    }

    private function getClassNameFromFile($file)
    {
        $realPath = $file->getRealpath();
        $fileName = str_replace($this->srcPath, '', $realPath);
        $className = str_replace('.php', '', $fileName);

        if(strpos($this->srcPath, 'test') !== false) {
            $className = "Tests/$className";
        }

        return str_replace('/', '\\', $className);
    }

    private function filterNonExistentClasses(array $classNames)
    {
        $classNames = array_filter($classNames, function ($className) {
            return class_exists($className);
        });

        return $classNames;
    }

    private function hasObject($fileHandler, $objectName): bool
    {
        $objectOpeningTag = sprintf('<complexType name="%s">', $objectName);
        do {
            $line = trim(fgets($fileHandler));
            if (strpos($line, 'complexType name') !== false && strcmp($line, $objectOpeningTag) > 0) {
                fseek($fileHandler, -strlen($line) - 1, SEEK_CUR);
                return false;
            }
        } while (strcmp($line, $objectOpeningTag) != 0 && !feof($fileHandler));

        if (feof($fileHandler)) {
            return false;
        }

        return true;
    }

    private function hasField($fileHandler, $fieldName): bool
    {
        $fieldOpeningTag = sprintf('<element name="%s"', $fieldName);
        do {
            $line = trim(fgets($fileHandler));
            if (strpos($line, $fieldOpeningTag) !== 0) {
                if (strpos($line, '</complexType>')) {
                    return false;
                }
                if (strpos($line, 'element name') !== false && strcmp($line, $fieldOpeningTag) > 0) {
                    fseek($fileHandler, -strlen($line) - 1, SEEK_CUR);
                    return false;
                }
            }
        } while (strpos($line, $fieldOpeningTag) !== 0 && !feof($fileHandler));

        if (feof($fileHandler)) {
            return false;
        }

        return true;
    }

    private function getFieldNames(?array $mapFieldAnnotation): ?array
    {
        if(!$mapFieldAnnotation) {
            return null;
        }

        $fieldNames = [];
        foreach ($mapFieldAnnotation as $propertyName => $annotation) {
            $fieldNames[] = $annotation->name;
        }

        return $this->sort($fieldNames);
    }

    /**
     * @param $fileHandler
     * @return bool
     * @throws FileHandlerNotInitializedException
     */
    private function initializeFileHandler($fileHandler): void
    {
        $sObjectOpeningTag = sprintf('<complexType name="%s">', 'sObject');
        do {
            $line = trim(fgets($fileHandler));
        } while (strcmp($line, $sObjectOpeningTag) !== 0 && !feof($fileHandler));

        if (feof($fileHandler)) {
            throw new FileHandlerNotInitializedException();
        }
    }

    private function sort($array)
    {
        sort($array);
        return $array;
    }

    public function buildErrorMessage(array $missingFields): string
    {
        $list = "These objects or fields are missing in wsdl:";
        foreach ($missingFields as $objectName => $fields) {
            foreach ($fields as $field) {
                $list .= "\n$objectName -> $field";
            }
        }

        return $list;
    }
}