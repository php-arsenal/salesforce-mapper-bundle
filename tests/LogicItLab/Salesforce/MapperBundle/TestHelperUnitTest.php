<?php

namespace Tests\LogicItLab\Salesforce\MapperBundle;

use LogicItLab\Salesforce\MapperBundle\Annotation\AnnotationReader;
use LogicItLab\Salesforce\MapperBundle\Annotation\Field;
use LogicItLab\Salesforce\MapperBundle\Annotation\SObject;
use LogicItLab\Salesforce\MapperBundle\TestHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tests\LogicItLab\Salesforce\MapperBundle\Resources\Classes\Account;
use Tests\LogicItLab\Salesforce\MapperBundle\Resources\Classes\Contact;
use Tests\LogicItLab\Salesforce\MapperBundle\Resources\Classes\User;

class TestHelperUnitTest extends TestCase
{
    /** @var AnnotationReader|MockObject */
    private $annotationReaderMock;

    /** @var TestHelper */
    private $testHelper;

    public function setUp(): void
    {
        $this->annotationReaderMock = $this->createMock(AnnotationReader::class);
        $this->testHelper = new TestHelper(
            $this->annotationReaderMock,
            str_replace('LogicItLab/Salesforce/MapperBundle', '', dirname(__FILE__)),
            'LogicItLab/Salesforce/MapperBundle/Resources/Classes',
            sprintf('%s/Resources/test.wsdl.xml', dirname(__FILE__)),
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

        $this->annotationReaderMock->expects($this->exactly(6))
            ->method('getSalesforceProperties')
            ->withConsecutive(
                [Account::class],
                [Contact::class],
                [User::class],
                [Account::class],
                [Contact::class],
                [User::class]
            )
            ->willReturnOnConsecutiveCalls(
                $accountProperties,
                $contactProperties,
                $userProperties,
                $accountProperties,
                $contactProperties,
                $userProperties
            );

        $missingFields = $this->testHelper->retrieveMissingFields();

        $this->assertTrue(in_array('City', $missingFields['User']),
            'Single missing field is not retrieved');

        $this->assertTrue(in_array('BillingCity', $missingFields['Account']) && in_array('BillingCity', $missingFields['Account']),
            'Consecutive missing fields are not retrieved');

        $this->assertTrue(in_array('entire object', $missingFields['Contact']),
            'Missing object is not retrieved');

        $this->assertEquals($expectedErrorMessage, $this->testHelper->buildErrorMessage($missingFields));
    }
}