<?php

namespace Tests\PhpArsenal\SalesforceMapperBundle\Mock;

use PhpArsenal\SalesforceMapperBundle\Annotation as Salesforce;

/**
 * @Salesforce\SObject(name="Contact")
 */
class ContactMock
{
    /**
     * @Salesforce\Field(name="Id")
     */
    protected $id;

    /**
     * @Salesforce\Field(name="FirstName")
     */
    protected $firstName;

    /**
     * @Salesforce\Field(name="LastName")
     */
    protected $lastName;
}