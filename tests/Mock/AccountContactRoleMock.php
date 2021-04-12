<?php

namespace Tests\PhpArsenal\SalesforceMapperBundle\Mock;

use PhpArsenal\SalesforceMapperBundle\Annotation as Salesforce;

/**
 * @Salesforce\SObject(name="AccountContactRole")
 */
class AccountContactRoleMock
{
    /**
     * @Salesforce\Field(name="Id")
     */
    protected $id;

    /**
     * @Salesforce\Relation(field="AccountId", name="Account",
     *   class="PhpArsenal\Salesforce\SalesforceMapperBundle\Tests\Mock\AccountMock"
     * )
     */
    protected $account;

    /**
     * @Salesforce\Relation(field="ContactId", name="Contact",
     *   class="PhpArsenal\Salesforce\SalesforceMapperBundle\Tests\Mock\ContactMock"
     * )
     */
    protected $contact;
}