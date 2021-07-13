<?php

namespace Tests\PhpArsenal\SalesforceMapperBundle\Stubs;

use PhpArsenal\SalesforceMapperBundle\Annotation as Salesforce;

/**
 * @Salesforce\SObject(name="IpAddress")
 */
class IpAddress
{
    /**
     * @var string
     * @Salesforce\Field(name="Ip__c")
     */
    protected $ip;

    /**
     * @var string
     */
    protected $port;

    public function __construct(string $ip, string $port)
    {
        $this->ip = $ip;
        $this->port = $port;
    }

    public function getId(): ?string
    {
        return null;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * @Salesforce\Field(name="Port__c")
     */
    public function getPort(): string
    {
        return $this->port;
    }
}
