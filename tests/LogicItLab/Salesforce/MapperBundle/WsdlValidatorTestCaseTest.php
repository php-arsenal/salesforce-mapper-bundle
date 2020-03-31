<?php

namespace Tests\LogicItLab\Salesforce\MapperBundle;

use LogicItLab\Salesforce\MapperBundle\WsdlValidatorTestCase;

class WsdlValidatorTestCaseTest extends WsdlValidatorTestCase
{
    public function modelAndWsdlDataProvider(): array
    {
        return [
            [   sprintf('%s/Stubs', dirname(__FILE__)),
                sprintf('%s/Resources/test.wsdl.xml', dirname(__FILE__))
            ]
        ];
    }
}