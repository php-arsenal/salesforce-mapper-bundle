<?php

namespace LogicItLab\Salesforce\MapperBundle;

use LogicItLab\Salesforce\MapperBundle\Annotation\AnnotationReader;
use LogicItLab\Salesforce\MapperBundle\Exception\FileHandlerNotInitializedException;
use Symfony\Component\Finder\Finder;

class TestHelper
{
    /** @var AnnotationReader */
    private $annotationReader;

    /** @var string */
    private $root;

    /** @var string */
    private $path;

    /** @var string */
    private $wsdlPath;

    /**
     * @param AnnotationReader $annotationReader
     * @param string $root
     * @param string $wsdlPath
     * @codeCoverageIgnore
     */
    public function __construct(AnnotationReader $annotationReader, string $root, string $path, string $wsdlPath)
    {
        $this->annotationReader = $annotationReader;
        $this->root = $root;
        $this->path = $path;
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
                $missingFields[$objectName] [] = "entire object";
                continue;
            }

            foreach ($fieldNames as $fieldName) {
                if ($this->hasField($fileHandler, $fieldName) === false) {
                    $missingFields[$objectName] [] = $fieldName;
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
        $filenames = [];
        $finderFiles = Finder::create()->files()->in($this->root.$this->path)->name('*.php');
        foreach ($finderFiles as $finderFile) {
            $realPath = $finderFile->getRealpath();
            $fileName = str_replace($this->root, '', $realPath);
            $className = str_replace('.php', '', $fileName);
            if(strpos($this->root, 'test') !== false) {
                $className = "Tests/$className";
            }
            $filenames[] = str_replace('/', '\\', $className);
        }

        $filenames = array_filter($filenames, function ($element) {
            return class_exists($element);
        });

        sort($filenames);
        return $filenames;
    }

    private function hasObject($fileHandler, $objectName): bool
    {
        $lookingFor = sprintf('<complexType name="%s">', $objectName);
        do {
            $line = trim(fgets($fileHandler));
            if (strpos($line, 'complexType name') !== false && strcmp($line, $lookingFor) > 0) {
                fseek($fileHandler, -strlen($line) - 1, SEEK_CUR);
                return false;
            }
        } while (strcmp($line, $lookingFor) != 0 && !feof($fileHandler));

        if (feof($fileHandler)) {
            return false;
        }

        return true;
    }

    private function hasField($fileHandler, $fieldName): bool
    {
        $lookingFor = sprintf('<element name="%s"', $fieldName);
        do {
            $line = trim(fgets($fileHandler));

            if (strpos($line, $lookingFor) !== 0) {

                if (strpos($line, '</complexType>')) {
                    return false;
                }
                if (strpos($line, 'element name') !== false && strcmp($line, $lookingFor) > 0) {
                    fseek($fileHandler, -strlen($line) - 1, SEEK_CUR);
                    return false;
                }
            }
        } while (strpos($line, $lookingFor) !== 0 && !feof($fileHandler));

        if (feof($fileHandler)) {
            return false;
        }

        return true;
    }

    /**
     * @param $fileHandler
     * @return bool
     * @throws FileHandlerNotInitializedException
     */
    private function initializeFileHandler($fileHandler): void
    {
        $lookingFor = sprintf('<complexType name="%s">', 'sObject');
        do {
            $line = trim(fgets($fileHandler));
        } while (strcmp($line, $lookingFor) !== 0 && !feof($fileHandler));

        if (feof($fileHandler)) {
            throw new FileHandlerNotInitializedException();
        }
    }

    private function getFieldNames(?array $mapFieldAnnotation): ?array
    {
        if(!$mapFieldAnnotation) {
            return null;
        }

        $fieldNames = [];
        foreach ($mapFieldAnnotation as $propertyName => $annotation) {
            $fieldNames [] = $annotation->name;
        }

        sort($fieldNames);
        return $fieldNames;
    }

    public function buildErrorMessage(array $missingFields): string
    {
        $list = "These objects or fields are missing in wsdl:";
        foreach ($missingFields as $object => $fields) {
            foreach ($fields as $field) {
                $list .= "\n$object -> $field";
            }
        }

        return $list;
    }
}