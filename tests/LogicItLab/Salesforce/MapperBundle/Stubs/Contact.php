<?php

namespace Tests\LogicItLab\Salesforce\MapperBundle\Stubs;

use DateTime;
use LogicItLab\Salesforce\MapperBundle\Annotation as Salesforce;

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