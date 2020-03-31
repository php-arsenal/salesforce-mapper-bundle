<?php

namespace LogicItLab\Salesforce\MapperBundle;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

abstract class WsdlValidatorTestCase extends KernelTestCase
{
    /** @var KernelInterface */
    protected $bootedKernel;

    public abstract function modelAndWsdlDataProvider(): array;

    /**
     * @param $modelsDir
     * @param $wsdlPath
     * @dataProvider modelAndWsdlDataProvider
     */
    public function testWsdlIsValid($modelsDir, $wsdlPath)
    {
        $this->markTestIncomplete();

        $validator = $this->buildValidator();
        $this->assertEmpty($validator->validate($modelsDir, $wsdlPath));
    }

    public function getParameter(string $parameterName)
    {
        return $this->getContainer()->getParameter($parameterName);
    }

    private function buildValidator(): WsdlValidator
    {
        $this->getService(WsdlValidator::class);
    }

    private function getService(string $className)
    {
        return $this->getContainer()->get($className);
    }

    private function getContainer(): ContainerInterface
    {
        return $this->getKernel()->getContainer();
    }

    private function getKernel(): KernelInterface
    {
        if (!$this->bootedKernel) {
            $this->bootedKernel = $this->bootKernel();
        }

        return $this->bootedKernel;
    }
}