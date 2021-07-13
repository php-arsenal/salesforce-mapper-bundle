<?php

namespace Tests\PhpArsenal\SalesforceMapperBundle\Functional;

use Doctrine\Common\Cache\VoidCache;
use PhpArsenal\SalesforceMapperBundle\Annotation\AnnotationReader;
use PhpArsenal\SalesforceMapperBundle\Mapper;
use PhpArsenal\SoapClient\ClientInterface;
use PhpArsenal\SoapClient\Result\DescribeSObjectResult;
use PhpArsenal\SoapClient\Result\DescribeSObjectResult\Field;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tests\PhpArsenal\SalesforceMapperBundle\Stubs\IpAddress;

class MapperTest extends TestCase
{
    private Mapper $mapper;
    private MockObject|ClientInterface $clientMock;

    public function setUp(): void
    {
        $this->clientMock = $this->createMock(ClientInterface::class);

        $this->mapper = new Mapper(
            $this->clientMock,
            new AnnotationReader(new \Doctrine\Common\Annotations\AnnotationReader()),
            new VoidCache()
        );
    }

    public function testMapsPropertiesAndMethodsCorrectly(): void
    {
        $ipField = new Field();
        $ipNameProperty = (new \ReflectionClass($ipField))->getProperty('name');
        $ipNameProperty->setAccessible(true);
        $ipNameProperty->setValue($ipField, 'Ip__c');

        $portField = new Field();
        $portNameProperty = (new \ReflectionClass($ipField))->getProperty('name');
        $portNameProperty->setAccessible(true);
        $portNameProperty->setValue($portField, 'Port__c');

        $createableProperty = (new \ReflectionClass($ipField))->getProperty('createable');
        $createableProperty->setAccessible(true);
        $createableProperty->setValue($ipField, true);
        $createableProperty->setValue($portField, true);

        $objectDescription = new DescribeSObjectResult();
        $nameProperty = (new \ReflectionClass($objectDescription))->getProperty('fields');
        $nameProperty->setAccessible(true);
        $nameProperty->setValue($objectDescription, [
            $ipField,
            $portField
        ]);

        $this->clientMock->expects($this->once())->method('describeSObjects')->willReturn([
            $objectDescription,
        ]);

        $ipAddressStub = new IpAddress('1.1.1.1', '8080');

        $salesforceObject = $this->mapper->mapToSalesforceObject($ipAddressStub);
        $this->assertEquals($salesforceObject->Ip__c, $ipAddressStub->getIp());
        $this->assertEquals($salesforceObject->Port__c, $ipAddressStub->getPort());
    }
}
