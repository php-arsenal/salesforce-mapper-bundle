<?php

namespace LogicItLab\Salesforce\MapperBundle;

use LogicItLab\Salesforce\MapperBundle\Annotation\AnnotationReader;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class WsdlValidatorTestCase extends KernelTestCase
{
    /** @var KernelInterface */
    protected $bootedKernel;

    /** @var WsdlValidator */
    private $wsdlValidator =  null;

    public function testWsdlIsValid()
    {
        $this->assertInstanceOf(WsdlValidator::class, $this->wsdlValidator, "Validator should be booted before run a test.");

        $missingFields = $this->wsdlValidator->validate();
        $this->assertEmpty($missingFields, $this->wsdlValidator->buildErrorMessage($missingFields));
    }

    public function bootValidator(string $baseProjectDir, string $modelDirPath, string $wsdlPath, string $namespacePrefix = "")
    {
        /** @var AnnotationReader $annotationReader */
        $annotationReader = $this->getService(AnnotationReader::class);
        $this->wsdlValidator = new WsdlValidator(
            $annotationReader,
            $baseProjectDir,
            $modelDirPath,
            $wsdlPath,
            $namespacePrefix
        );
    }

    public function getService(string $className)
    {
        return $this->getContainer()->get($className);
    }

    public function getParameter(string $parameterName)
    {
        return $this->getContainer()->getParameter($parameterName);
    }

    public function getContainer(): ContainerInterface
    {
        return $this->getKernel()->getContainer();
    }

    public function getKernel(): KernelInterface
    {
        if (!$this->bootedKernel) {
            $this->bootedKernel = static::bootKernel();
        }

        return $this->bootedKernel;
    }
}