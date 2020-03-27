<?php

namespace LogicItLab\Salesforce\MapperBundle;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class WsdlValidatorTestCase extends KernelTestCase
{
    /** @var WsdlValidator */
    private $wsdlValidator =  null;

    public function testWsdlIsValid()
    {
        $this->assertInstanceOf(WsdlValidator::class, $this->wsdlValidator, "Validator should be booted before run a test.");
        $this->assertEmpty($this->wsdlValidator->retrieveMissingFields());
    }

    public function bootValidator(string $baseProjectDir, string $modelDirPath, string $wsdlPath)
    {
        $this->wsdlValidator = $this->bootKernel()->getContainer()->get(WsdlValidator::class);
        $this->wsdlValidator->setBaseProjectDir($baseProjectDir);
        $this->wsdlValidator->setModelDirPath($modelDirPath);
        $this->wsdlValidator->setWsdlPath($wsdlPath);
    }
}