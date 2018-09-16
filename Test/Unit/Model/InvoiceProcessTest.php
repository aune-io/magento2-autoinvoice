<?php

namespace Aune\AutoInvoice\Test\Unit\Model;

use Magento\Framework\DB\Transaction;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice as OrderInvoice;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\Service\InvoiceService;

use Aune\AutoInvoice\Api\InvoiceProcessInterface;
use Aune\AutoInvoice\Helper\Data as HelperData;
use Aune\AutoInvoice\Model\InvoiceProcess;

class InvoiceProcessTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var HelperData|PHPUnit_Framework_MockObject_MockObject
     */
    private $helperDataMock;
    
    /**
     * @var OrderCollectionFactory|PHPUnit_Framework_MockObject_MockObject
     */
    private $orderCollectionFactoryMock;
    
    /**
     * @var Transaction|PHPUnit_Framework_MockObject_MockObject
     */
    private $transactionMock;
    
    /**
     * @var InvoiceService|PHPUnit_Framework_MockObject_MockObject
     */
    private $invoiceServiceMock;
    
    /**
     * @var InvoiceProcess
     */
    private $invoiceProcess;
    
    protected function setUp()
    {
        $this->helperDataMock = $this->getMockBuilder(HelperData::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderCollectionFactoryMock = $this->getMockBuilder(OrderCollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->transactionMock = $this->getMockBuilder(Transaction::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->invoiceServiceMock = $this->getMockBuilder(InvoiceService::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->invoiceProcess = new InvoiceProcess(
            $this->helperDataMock,
            $this->orderCollectionFactoryMock,
            $this->transactionMock,
            $this->invoiceServiceMock
        );
    }

    /**
     * Test class service contract
     */
    public function testServiceContract()
    {
        $this->assertInstanceOf(
            InvoiceProcessInterface::class,
            $this->invoiceProcess
        );
    }
    
    /**
     * @covers \Aune\AutoInvoice\Model\InvoiceProcess::getOrdersToInvoice
     */
    public function testGetOrdersToInvoice()
    {
        $orderCollectionMock = $this->getMockBuilder(OrderCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->orderCollectionFactoryMock->expects(self::once())
            ->method('create')
            ->willReturn($orderCollectionMock);
        
        $orderCollectionMock->expects(self::exactly(2))
            ->method('addFieldToFilter')
            ->willReturn($orderCollectionMock);
        
        $this->assertEquals(
            $this->invoiceProcess->getOrdersToInvoice(),
            $orderCollectionMock
        );
    }
    
    /**
     * @covers \Aune\AutoInvoice\Model\InvoiceProcess::invoice
     */
    public function testInvoice()
    {
        $orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $invoiceMock = $this->getMockBuilder(OrderInvoice::class)
            ->disableOriginalConstructor()
            ->setMethods(['setRequestedCaptureCase', 'register'])
            ->getMock();
        
        $this->invoiceServiceMock->expects(self::once())
            ->method('prepareInvoice')
            ->with($orderMock)
            ->willReturn($invoiceMock);
        
        $invoiceMock->expects(self::once())
            ->method('setRequestedCaptureCase')
            ->with(OrderInvoice::CAPTURE_OFFLINE);
        
        $invoiceMock->expects(self::once())
            ->method('register');
        
        $this->transactionMock->expects(self::exactly(2))
            ->method('addObject')
            ->willReturn($this->transactionMock);
        
        $this->transactionMock->expects(self::once())
            ->method('save');
        
        $this->invoiceProcess->invoice($orderMock);
    }
}
