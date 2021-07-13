<?php

namespace Tests\PhpArsenal\SalesforceMapperBundle\Unit;

use PhpArsenal\SalesforceMapperBundle\Annotation\AnnotationReader;
use PhpArsenal\SalesforceMapperBundle\MappedBulkSaver;
use PhpArsenal\SalesforceMapperBundle\Mapper;
use PhpArsenal\SalesforceMapperBundle\Model\Account;
use PhpArsenal\SoapClient\BulkSaver;
use PhpArsenal\SoapClient\Result\SaveResult;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MappedBulkSaverTest extends TestCase
{
    /** @var MappedBulkSaver */
    private $mappedBulkSaver;

    /** @var BulkSaver|MockObject */
    private $bulkSaverMock;

    /** @var Mapper|MockObject */
    private $mapperMock;

    /** @var AnnotationReaderTest|MockObject */
    private $annotationReaderMock;

    public function setUp(): void
    {
        $this->bulkSaverMock = $this->createMock(BulkSaver::class);
        $this->mapperMock = $this->createMock(Mapper::class);
        $this->annotationReaderMock = $this->createMock(AnnotationReader::class);

        $this->mappedBulkSaver = new MappedBulkSaver(
            $this->bulkSaverMock,
            $this->mapperMock,
            $this->annotationReaderMock
        );
    }

    public function testFlushesClearsOnException(): void
    {
        $account = new Account();
        $account->setName('Aa');
        $account2 = new Account();
        $account2->setName('Bb');

        $objectMapping = new \stdClass();
        $objectMapping->name = 'Account';

        $this->annotationReaderMock->expects($this->exactly(2))
            ->method('getSalesforceObject')
            ->willReturnOnConsecutiveCalls(
                $objectMapping,
                $objectMapping
            );

        $mappedAccount = new \stdClass();
        $mappedAccount->Name = $account->getName();
        $mappedAccount2 = new \stdClass();
        $mappedAccount2->Name = $account2->getName();

        $this->mapperMock->expects($this->exactly(2))
            ->method('mapToSalesforceObject')
            ->willReturnOnConsecutiveCalls(
                $mappedAccount,
                $mappedAccount2
            );

        $this->mappedBulkSaver->save($account);
        $this->mappedBulkSaver->save($account2);

        $exception = new \Exception('Some exception');

        $this->bulkSaverMock->expects($this->once())
            ->method('flush')
            ->willThrowException($exception);

        $this->assertEquals(2, $this->mappedBulkSaver->itemsInQueue('created'));

        $this->bulkSaverMock->expects($this->once())
            ->method('clear');

        $this->expectExceptionObject($exception);

        $this->mappedBulkSaver->flush();

        $this->assertEquals(0, $this->mappedBulkSaver->itemsInQueue('created'));
    }

    public function testFlushesAndClearsOnSuccess(): void
    {
        $account = new Account();
        $account->setName('Aa');
        $account2 = new Account();
        $account2->setName('Bb');

        $objectMapping = new \stdClass();
        $objectMapping->name = 'Account';

        $this->annotationReaderMock->expects($this->exactly(2))
            ->method('getSalesforceObject')
            ->willReturnOnConsecutiveCalls(
                $objectMapping,
                $objectMapping
            );

        $mappedAccount = new \stdClass();
        $mappedAccount->Name = $account->getName();
        $mappedAccount2 = new \stdClass();
        $mappedAccount2->Name = $account2->getName();

        $this->mapperMock->expects($this->exactly(2))
            ->method('mapToSalesforceObject')
            ->willReturnOnConsecutiveCalls(
                $mappedAccount,
                $mappedAccount2
            );

        $this->mappedBulkSaver->save($account);
        $this->mappedBulkSaver->save($account2);

        $savedAccount = clone $account;
        $savedAccount->setId(1);

        $savedAccount2 = clone $account2;
        $savedAccount2->setId(1);

        $result = $this->createMock(SaveResult::class);
        $result->expects($this->any())
            ->method('isSuccess')
            ->willReturn(true);

        $result2 = $this->createMock(SaveResult::class);
        $result2->expects($this->any())
            ->method('isSuccess')
            ->willReturn(true);

        $this->bulkSaverMock->expects($this->once())
            ->method('flush')
            ->willReturn([
                'created' => [$result, $result2],
                'upserted' => []
            ]);

        $this->assertEquals(2, $this->mappedBulkSaver->itemsInQueue('created'));

        $this->bulkSaverMock->expects($this->once())
            ->method('clear');

        $this->mappedBulkSaver->flush();

        $this->assertEquals(0, $this->mappedBulkSaver->itemsInQueue('created'));
    }
}
