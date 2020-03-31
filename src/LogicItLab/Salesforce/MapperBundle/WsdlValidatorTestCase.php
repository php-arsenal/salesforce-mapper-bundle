<?php

namespace LogicItLab\Salesforce\MapperBundle;

use LogicItLab\Salesforce\MapperBundle\Annotation\AnnotationReader;
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
        $this->assertEmpty($this->buildValidator()->validate($modelsDir, $wsdlPath));
    }

    private function buildValidator(): WsdlValidator
    {
        $annotationReader = new AnnotationReader(new \Doctrine\Common\Annotations\AnnotationReader());
        return new WsdlValidator($annotationReader);
    }
}