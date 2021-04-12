<?php

namespace Tests\PhpArsenal\SalesforceMapperBundle\Mock;

use PhpArsenal\SalesforceMapperBundle\Annotation as Salesforce;

/**
 * @Salesforce\SObject(name="Account")
 */
class AccountMock
{
    /**
     * @Salesforce\Field(name="Id")
     */
    protected $id;

    /**
     * @Salesforce\Field(name="Name")
     */
    protected $name;

    /**
     * @Salesforce\Relation(name="AccountContactRoles",
     *  class="PhpArsenal\Salesforce\SalesforceMapperBundle\Tests\Mock\AccountContactRoleMock"
     * )
     */
    protected $accountContactRoles;

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getAccountContactRoles()
    {
        return $this->accountContactRoles;
    }

    public function setAccountContactRoles($accountContactRoles)
    {
        $this->accountContactRoles = $accountContactRoles;
    }
}