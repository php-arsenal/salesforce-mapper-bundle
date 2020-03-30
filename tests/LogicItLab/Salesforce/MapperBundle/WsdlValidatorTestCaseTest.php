<?php

namespace Tests\LogicItLab\Salesforce\MapperBundle;

use LogicItLab\Salesforce\MapperBundle\Annotation\AnnotationReader;
use LogicItLab\Salesforce\MapperBundle\Annotation\Field;
use LogicItLab\Salesforce\MapperBundle\Annotation\SObject;
use LogicItLab\Salesforce\MapperBundle\WsdlValidator;
use LogicItLab\Salesforce\MapperBundle\WsdlValidatorTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WsdlValidatorTestCaseTest extends WsdlValidatorTestCase
{
    /** @var WsdlValidatorTestCase */
    private $wsdlValidatorTestCase;

    public function setUp(): void
    {
        $this->wsdlValidatorTestCase = new WsdlValidatorTestCase();
    }

    public function testBuildValidator()
    {
        sprintf('%s/Stubs', dirname(__FILE__));
        sprintf('%s/Resources/test.wsdl.xml', dirname(__FILE__));

        $this->assertInstanceOf(WsdlValidator::class, $this->buildValidator());
    }
}