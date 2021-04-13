<?php

namespace PhpArsenal\SalesforceMapperBundle;

use PhpArsenal\SalesforceMapperBundle\Annotation\AnnotationReader;
use PHPUnit\Framework\TestCase;

abstract class WsdlValidatorTestCase extends TestCase
{
    public abstract function modelAndWsdlDataProvider(): array;

    /**
     * @param $modelsDir
     * @param $wsdlPath
     * @dataProvider modelAndWsdlDataProvider
     */
    public function testWsdlIsValid($modelsDir, $wsdlPath)
    {
        $missingFields = $this->buildValidator()->validate($modelsDir, $wsdlPath);

        $this->assertEmpty($missingFields, $this->buildValidator()->buildErrorMessage($missingFields));
    }

    private function buildValidator(): WsdlValidator
    {
        $annotationReader = new AnnotationReader(new \Doctrine\Common\Annotations\AnnotationReader());

        return new WsdlValidator($annotationReader);
    }
}