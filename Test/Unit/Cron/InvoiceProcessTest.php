<?php

namespace Aune\AutoInvoice\Test\Unit\Console;

use Psr\Log\LoggerInterface;
use Magento\Sales\Model\Order;
use Aune\AutoInvoice\Api\InvoiceProcessInterface;
use Aune\AutoInvoice\Helper\Data as HelperData;
use Aune\AutoInvoice\Cron\InvoiceProcess;

/**
 * @SuppressWarnings(PHPMD.ShortVariable)
 */
class InvoiceProcessTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var HelperData|PHPUnit_Framework_MockObject_MockObject
     */
    private $helperDataMock;
    
    /**
     * @var LoggerInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;
    
    /**
     * @var InvoiceProcessInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $invoiceProcessMock;
    
    /**
     * @var InvoiceProcess
     */
    private $invoiceProcess;
    
    protected function setUp()
    {
        $this->helperDataMock = $this->getMockBuilder(HelperData::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->invoiceProcessMock = $this->createMock(InvoiceProcessInterface::class);
        
        $this->invoiceProcess = new InvoiceProcess(
            $this->helperDataMock,
            $this->loggerMock,
            $this->invoiceProcessMock
        );
    }
    
    /**
     * @covers \Aune\AutoInvoice\Cron\InvoiceProcess::execute
     */
    public function testExecuteDisabled()
    {
        $this->helperDataMock->expects(self::exactly(1))
            ->method('isCronEnabled')
            ->willReturn(false);
        
        $this->invoiceProcessMock->expects(self::exactly(0))
            ->method('getOrdersToInvoice');
        
        $this->invoiceProcessMock->expects(self::exactly(0))
            ->method('invoice');
        
        $this->invoiceProcess->execute();
    }
    
    /**
     * @covers \Aune\AutoInvoice\Cron\InvoiceProcess::execute
     */
    public function testExecute()
    {
        $this->helperDataMock->expects(self::exactly(1))
            ->method('isCronEnabled')
            ->willReturn(true);
        
        $n = 10;
        $orderMocks = [];
        
        for ($i=0; $i<$n; $i++) {
            $orderMock = $this->getMockBuilder(Order::class)
                ->disableOriginalConstructor()
                ->getMock();
            
            $orderMocks []= $orderMock;
        }
        
        $this->invoiceProcessMock->expects(self::once())
            ->method('getOrdersToInvoice')
            ->willReturn($orderMocks);
        
        $this->invoiceProcessMock->expects(self::exactly(count($orderMocks)))
            ->method('invoice');
        
        $this->invoiceProcess->execute();
    }
}
