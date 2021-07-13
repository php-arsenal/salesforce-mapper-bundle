<?php

namespace Tests\PhpArsenal\SalesforceMapperBundle\Unit;

use PhpArsenal\SalesforceMapperBundle\WsdlValidatorTestCase;

class WsdlValidatorTestCaseTest extends WsdlValidatorTestCase
{
    public function modelAndWsdlDataProvider(): array
    {
        return [
            [   sprintf('%s/../Stubs', dirname(__FILE__)),
                sprintf('%s/../Resources/test.full.wsdl.xml', dirname(__FILE__))
            ]
        ];
    }
}