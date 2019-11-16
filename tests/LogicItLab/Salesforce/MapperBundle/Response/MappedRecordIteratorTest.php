<?php

namespace Tests\LogicItLab\Salesforce\MapperBundle\Response;

use LogicItLab\Salesforce\MapperBundle\Mapper;
use LogicItLab\Salesforce\MapperBundle\Response\MappedRecordIterator;
use Phpforce\SoapClient\Result\RecordIterator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit_Framework_TestCase;

class MappedRecordIteratorTest extends PHPUnit_Framework_TestCase
{
    public function testFirstDoesNotThrowOutOfBounds()
    {
        /** @var RecordIterator|MockObject $recordIterator */
        $recordIterator = $this->getMockBuilder('\Phpforce\SoapClient\Result\RecordIterator')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Mapper|MockObject $mapper */
        $mapper = $this->getMockBuilder('\LogicItLab\Salesforce\MapperBundle\Mapper')
            ->disableOriginalConstructor()
            ->getMock();

        $iterator = new MappedRecordIterator($recordIterator, $mapper, 'Account');

        $this->assertNull($iterator->first());
    }
}