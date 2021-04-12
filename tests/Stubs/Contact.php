<?php

namespace Tests\PhpArsenal\SalesforceMapperBundle\Stubs;

use PhpArsenal\SalesforceMapperBundle\Annotation as Salesforce;

/**
 * @Salesforce\SObject(name="Contact")
 */
class Contact
{
    /**
     * @var string
     * @Salesforce\Field(name="AccountId")
     */
    protected $accountId;
}