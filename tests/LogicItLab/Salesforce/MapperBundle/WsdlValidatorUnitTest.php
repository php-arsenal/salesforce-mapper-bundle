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

    public function setUp(): void
    {
        $this->annotationReaderMock = $this->createMock(AnnotationReader::class);
        $this->wsdlValidator = new WsdlValidator(
            $this->annotationReaderMock,
            str_replace('LogicItLab/Salesforce/MapperBundle', '', dirname(__FILE__)),
            'LogicItLab/Salesforce/MapperBundle/Stubs',
            sprintf('%s/Resources/test.wsdl.xml', dirname(__FILE__))
        );
    }

    public function testRetrievesMissingFields()
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

        $expectedErrorMessage = 'These objects or fields are missing in wsdl:
Account -> BillingCity
Account -> BillingCountry
Contact -> AccountId
User -> City';

        $this->annotationReaderMock->expects($this->exactly(3))
            ->method('getSalesforceProperties')
            ->willReturnOnConsecutiveCalls(
                $accountProperties,
                $contactProperties,
                $userProperties
            );

        $missingFields = $this->wsdlValidator->retrieveMissingFields();

        $this->assertEquals($expectedErrorMessage, $this->wsdlValidator->buildErrorMessage($missingFields));
    }
}