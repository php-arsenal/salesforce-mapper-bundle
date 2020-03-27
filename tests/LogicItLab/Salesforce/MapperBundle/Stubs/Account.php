<?php

namespace Tests\LogicItLab\Salesforce\MapperBundle\Stubs;

use LogicItLab\Salesforce\MapperBundle\Annotation as Salesforce;

/**
 * @Salesforce\SObject(name="Account")
 */
class Account
{
    /**
     * @var string
     * @Salesforce\Field(name="AccountNumber")
     */
    protected $accountNumber;

    /**
     * @var string
     * @Salesforce\Field(name="BillingCity")
     */
    protected $billingCity;

    /**
     * @var string
     * @Salesforce\Field(name="BillingCountry")
     */
    protected $billingCountry;

    /**
     * @var string
     * @Salesforce\Field(name="Owner")
     */
    protected $owner;
}