<?php

namespace Tests\LogicItLab\Salesforce\MapperBundle;

use LogicItLab\Salesforce\MapperBundle\Annotation\AnnotationReader;
use LogicItLab\Salesforce\MapperBundle\Annotation\Field;
use LogicItLab\Salesforce\MapperBundle\Annotation\SObject;
use LogicItLab\Salesforce\MapperBundle\WsdlValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WsdlValidatorUnitTest extends TestCase
{
    /** @var AnnotationReader|MockObject */
    private $annotationReaderMock;

    /** @var WsdlValidator */
    private $wsdlValidator;

    private $expectedMissingFields = [
        'Account' => [
            'BillingCity',
            'BillingCountry'
        ],
        'Contact' => [
            'AccountId'
        ],
        'User' => [
            'City'
        ]
    ];

    private $expectedErrorMessage = 'These objects or fields are missing in wsdl:
Account -> BillingCity
Account -> BillingCountry
Contact -> AccountId
User -> City';

    public function setUp(): void
    {
        $this->annotationReaderMock = $this->createMock(AnnotationReader::class);
        $this->wsdlValidator = new WsdlValidator($this->annotationReaderMock);
    }

    public function testValidate()
    {

        $accountProperties = array(
            'object' => new SObject(['name' => 'Account']),
            'relations' => array(),
            'fields' => [
                new Field(['name' => 'AccountNumber']),
                new Field(['name' => 'BillingCity']),
                new Field(['name' => 'BillingCountry']),
                new Field(['name' => 'Owner']),
            ]
        );

        $contactProperties = array(
            'object' => new SObject(['name' => 'Contact']),
            'relations' => array(),
            'fields' => [
                new Field(['name' => 'AccountId']),
            ]
        );

        $userProperties = array(
            'object' => new SObject(['name' => 'User']),
            'relations' => array(),
            'fields' => [
                new Field(['name' => 'City']),
                new Field(['name' => 'Country']),
            ]
        );

        $this->annotationReaderMock->expects($this->exactly(3))
            ->method('getSalesforceProperties')
            ->willReturnOnConsecutiveCalls(
                $accountProperties,
                $contactProperties,
                $userProperties
            );

        $missingFields = $this->wsdlValidator->validate(
            sprintf('%s/Stubs', dirname(__FILE__)),
            sprintf('%s/Resources/test.wsdl.xml', dirname(__FILE__))
        );

        $this->assertEquals($this->expectedMissingFields, $missingFields);
    }

    public function testBuildMessage()
    {
        $this->assertEquals($this->expectedErrorMessage, $this->wsdlValidator->buildErrorMessage($this->expectedMissingFields));
    }
}