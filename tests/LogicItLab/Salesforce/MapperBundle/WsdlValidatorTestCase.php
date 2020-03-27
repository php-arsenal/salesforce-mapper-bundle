<?php

namespace LogicItLab\Salesforce\MapperBundle;

use LogicItLab\Salesforce\MapperBundle\Annotation\AnnotationReader;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class WsdlValidatorTestCase extends KernelTestCase
{
    /** @var WsdlValidator */
    private $wsdlValidator =  null;

    public function testWsdlIsValid()
    {
        $this->assertInstanceOf(WsdlValidator::class, $this->wsdlValidator, "Validator should be booted before run a test.");
        $this->assertEmpty($this->wsdlValidator->validate());
    }

    public function bootValidator(string $baseProjectDir, string $modelDirPath, string $wsdlPath)
    {
        /** @var AnnotationReader $annotationReader */
        $annotationReader = $this->bootKernel()->getContainer()->get(AnnotationReader::class);
        $this->wsdlValidator = new WsdlValidator(
            $annotationReader,
            $baseProjectDir,
            $modelDirPath,
            $wsdlPath
        );
    }
}