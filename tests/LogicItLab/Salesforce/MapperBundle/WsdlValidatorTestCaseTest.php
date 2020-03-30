<?php

namespace Tests\LogicItLab\Salesforce\MapperBundle;

use LogicItLab\Salesforce\MapperBundle\WsdlValidator;
use PHPUnit\Framework\TestCase;

class WsdlValidatorTestCaseTest extends TestCase
{
    public function testBuildValidator()
    {
        $this->markTestIncomplete();
        $this->assertInstanceOf(WsdlValidator::class, $this->getWsdlValidator());
    }
}