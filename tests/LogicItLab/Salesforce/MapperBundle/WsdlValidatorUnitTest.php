<?php

namespace Tests\LogicItLab\Salesforce\MapperBundle;

use LogicItLab\Salesforce\MapperBundle\Annotation\AnnotationReader;
use LogicItLab\Salesforce\MapperBundle\Annotation\Field;
use LogicItLab\Salesforce\MapperBundle\Annotation\SObject;
use LogicItLab\Salesforce\MapperBundle\WsdlValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tests\LogicItLab\Salesforce\MapperBundle\Stubs\Account;
use Tests\LogicItLab\Salesforce\MapperBundle\Stubs\Contact;
use Tests\LogicItLab\Salesforce\MapperBundle\Stubs\User;

class WsdlValidatorUnitTest extends TestCase
{
    /** @var AnnotationReader|MockObject */
    private $annotationReaderMock;

    /** @var WsdlValidator */
    private $testHelper;

    public function setUp(): void
    {
        $this->annotationReaderMock = $this->createMock(AnnotationReader::class);
        $this->testHelper = new WsdlValidator(
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
Contact -> entire object
User -> City';

        $this->annotationReaderMock->expects($this->exactly(3))
            ->method('getSalesforceProperties')
            ->withConsecutive(
                [Account::class],
                [Contact::class],
                [User::class]
            )
            ->willReturnOnConsecutiveCalls(
                $accountProperties,
                $contactProperties,
                $userProperties
            );

        $missingFields = $this->testHelper->retrieveMissingFields();

        $this->assertTrue(in_array('BillingCity', $missingFields['Account']) && in_array('BillingCity', $missingFields['Account']),
            'Consecutive missing fields are not retrieved');

        $this->assertTrue(in_array('entire object', $missingFields['Contact']),
            'Missing object is not retrieved');

        $this->assertTrue(in_array('City', $missingFields['User']),
            'Object consecutive of a missing object field is not retrieved');

        $this->assertEquals($expectedErrorMessage, $this->testHelper->buildErrorMessage($missingFields));
    }
}