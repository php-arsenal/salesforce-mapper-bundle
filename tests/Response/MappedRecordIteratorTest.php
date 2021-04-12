<?php

namespace Tests\PhpArsenal\SalesforceMapperBundle\Response;

use PhpArsenal\SalesforceMapperBundle\Mapper;
use PhpArsenal\SalesforceMapperBundle\Response\MappedRecordIterator;
use PhpArsenal\SoapClient\Result\RecordIterator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit_Framework_TestCase;

class MappedRecordIteratorTest extends PHPUnit_Framework_TestCase
{
    public function testFirstDoesNotThrowOutOfBounds()
    {
        /** @var RecordIterator|MockObject $recordIterator */
        $recordIterator = $this->getMockBuilder('\PhpArsenal\SoapClient\Result\RecordIterator')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Mapper|MockObject $mapper */
        $mapper = $this->getMockBuilder('\PhpArsenal\SalesforceMapperBundle\Mapper')
            ->disableOriginalConstructor()
            ->getMock();

        $iterator = new MappedRecordIterator($recordIterator, $mapper, 'Account');

        $this->assertNull($iterator->first());
    }
}