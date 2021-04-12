<?php

namespace Tests\PhpArsenal\SalesforceMapperBundle\Stubs;

use PhpArsenal\SalesforceMapperBundle\Annotation as Salesforce;

/**
 * @Salesforce\SObject(name="User")
 */
class User
{
    /**
     * @var string
     * @Salesforce\Field(name="City")
     */
    protected $city;

    /**
     * @var string
     * @Salesforce\Field(name="Country")
     */
    protected $country;
}