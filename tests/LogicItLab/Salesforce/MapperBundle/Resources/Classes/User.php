<?php

namespace Tests\LogicItLab\Salesforce\MapperBundle\Resources\Classes;

use LogicItLab\Salesforce\MapperBundle\Annotation as Salesforce;

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