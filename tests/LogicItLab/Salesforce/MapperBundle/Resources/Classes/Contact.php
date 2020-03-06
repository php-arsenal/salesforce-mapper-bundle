<?php

namespace Tests\LogicItLab\Salesforce\MapperBundle\Resources\Classes;

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