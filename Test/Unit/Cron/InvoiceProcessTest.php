<?php

namespace Aune\AutoInvoice\Test\Unit\Cron;

use Psr\Log\LoggerInterface;
use Magento\Sales\Model\Order;
use Aune\AutoInvoice\Api\Data\InvoiceProcessItemInterface;
use Aune\AutoInvoice\Api\InvoiceProcessInterface;
use Aune\AutoInvoice\Api\InvoiceProcessInterfaceFactory;
use Aune\AutoInvoice\Helper\Data as HelperData;
use Aune\AutoInvoice\Cron\InvoiceProcess;

/**
 * @SuppressWarnings(PHPMD.ShortVariable)
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\Aune\AutoInvoice\Cron\InvoiceProcess::class)]
class InvoiceProcessTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var HelperData|\PHPUnit\Framework\MockObject\MockObject
     */
    private $helperDataMock;
    
    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;
    
    /**
     * @var InvoiceProcessInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $invoiceProcessMock;

    /**
     * @var InvoiceProcessInterfaceFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $invoiceProcessFactoryMock;
    
    /**
     * @var InvoiceProcess
     */
    private $invoiceProcess;
    
    protected function setUp(): void
    {
        $this->helperDataMock = $this->getMockBuilder(HelperData::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->invoiceProcessMock = $this->createMock(InvoiceProcessInterface::class);
        $this->invoiceProcessFactoryMock = $this->getMockBuilder(InvoiceProcessInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->invoiceProcessFactoryMock->method('create')->willReturn($this->invoiceProcessMock);
        
        $this->invoiceProcess = new InvoiceProcess(
            $this->helperDataMock,
            $this->loggerMock,
            $this->invoiceProcessFactoryMock
        );
    }
    
    public function testExecuteDisabled()
    {
        $this->helperDataMock->expects(self::exactly(1))
            ->method('isCronEnabled')
            ->willReturn(false);
        
        $this->invoiceProcessMock->expects(self::exactly(0))
            ->method('getItemsToProcess');
        
        $this->invoiceProcessMock->expects(self::exactly(0))
            ->method('invoice');
        
        $this->invoiceProcess->execute();
    }
    
    public function testExecute()
    {
        $this->helperDataMock->expects(self::exactly(1))
            ->method('isCronEnabled')
            ->willReturn(true);
        
        $itemMocks = [];
        for ($i=0; $i<10; $i++) {
            $orderMock = $this->getMockBuilder(Order::class)
                ->disableOriginalConstructor()
                ->getMock();
            
            $itemMock = $this->createMock(InvoiceProcessItemInterface::class);
            $itemMock->expects(self::any())
                ->method('getOrder')
                ->willReturn($orderMock);
            
            $itemMocks []= $itemMock;
        }
        
        $this->invoiceProcessMock->expects(self::once())
            ->method('getItemsToProcess')
            ->willReturn($itemMocks);
        
        $this->invoiceProcessMock->expects(self::exactly(count($itemMocks)))
            ->method('invoice');
        
        $this->invoiceProcess->execute();
    }
}
