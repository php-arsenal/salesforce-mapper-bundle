<?php

namespace Tests\PhpArsenal\SalesforceMapperBundle;

use PhpArsenal\SalesforceMapperBundle\Annotation\AnnotationReader;
use PhpArsenal\SalesforceMapperBundle\Annotation\Field;
use PhpArsenal\SalesforceMapperBundle\Annotation\SObject;
use PhpArsenal\SalesforceMapperBundle\WsdlValidator;
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

        $accountProperties = [
            'object' => new SObject(['name' => 'Account']),
            'relations' => [],
            'fields' => [
                new Field(['name' => 'AccountNumber']),
                new Field(['name' => 'BillingCity']),
                new Field(['name' => 'BillingCountry']),
                new Field(['name' => 'Owner']),
            ]
        ];

        $contactProperties = [
            'object' => new SObject(['name' => 'Contact']),
            'relations' => [],
            'fields' => [
                new Field(['name' => 'AccountId']),
            ]
        ];

        $userProperties = [
            'object' => new SObject(['name' => 'User']),
            'relations' => [],
            'fields' => [
                new Field(['name' => 'City']),
                new Field(['name' => 'Country']),
            ]
        ];

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