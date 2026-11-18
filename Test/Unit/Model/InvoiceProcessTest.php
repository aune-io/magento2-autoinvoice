<?php

namespace Aune\AutoInvoice\Test\Unit\Model;

use ArrayIterator;
use Magento\Framework\DB\Transaction;
use Magento\Framework\DB\TransactionFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Invoice as OrderInvoice;
use Psr\Log\LoggerInterface;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Status\Collection as OrderStatusCollection;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory as OrderStatusCollectionFactory;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Sales\Model\Service\InvoiceServiceFactory;

use Aune\AutoInvoice\Api\Data\InvoiceProcessItemInterface;
use Aune\AutoInvoice\Api\Data\InvoiceProcessItemInterfaceFactory;
use Aune\AutoInvoice\Api\InvoiceProcessInterface;
use Aune\AutoInvoice\Helper\Data as HelperData;
use Aune\AutoInvoice\Model\InvoiceProcess;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\Aune\AutoInvoice\Model\InvoiceProcess::class)]
class InvoiceProcessTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var HelperData|\PHPUnit\Framework\MockObject\MockObject
     */
    private $helperDataMock;
    
    /**
     * @var OrderCollectionFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderCollectionFactoryMock;
    
    /**
     * @var OrderStatusCollectionFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderStatusCollectionFactoryMock;
    
    /**
     * @var InvoiceProcessItemInterfaceFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $invoiceProcessItemFactoryMock;
    
    /**
     * @var Transaction|\PHPUnit\Framework\MockObject\MockObject
     */
    private $transactionMock;

    /**
     * @var TransactionFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $transactionFactoryMock;

    /**
     * @var InvoiceSender|\PHPUnit\Framework\MockObject\MockObject
     */
    private $invoiceSenderMock;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;
    
    /**
     * @var InvoiceServiceFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $invoiceServiceFactoryMock;
    
    /**
     * @var InvoiceProcess
     */
    private $invoiceProcess;
    
    protected function setUp(): void
    {
        $this->helperDataMock = $this->getMockBuilder(HelperData::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderCollectionFactoryMock = $this->getMockBuilder(OrderCollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->orderStatusCollectionFactoryMock = $this->getMockBuilder(OrderStatusCollectionFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        
        $this->invoiceProcessItemFactoryMock = $this->getMockBuilder(InvoiceProcessItemInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        
        $this->transactionMock = $this->getMockBuilder(Transaction::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->transactionFactoryMock = $this->getMockBuilder(TransactionFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->transactionFactoryMock->method('create')->willReturn($this->transactionMock);

        $this->invoiceServiceFactoryMock = $this->getMockBuilder(InvoiceServiceFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        
        $this->invoiceSenderMock = $this->getMockBuilder(InvoiceSender::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->invoiceProcess = new InvoiceProcess(
            $this->helperDataMock,
            $this->orderCollectionFactoryMock,
            $this->orderStatusCollectionFactoryMock,
            $this->invoiceProcessItemFactoryMock,
            $this->transactionFactoryMock,
            $this->invoiceServiceFactoryMock,
            $this->invoiceSenderMock,
            $this->loggerMock
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
    
    public function testGetItemsToProcess()
    {
        $dstStatus = 'complete';
        $captureMode = 'offline';
        
        $this->helperDataMock->expects(self::once())
            ->method('getProcessingRules')
            ->willReturn([[
                HelperData::RULE_SOURCE_STATUS => 'processing',
                HelperData::RULE_PAYMENT_METHOD => HelperData::RULE_PAYMENT_METHOD_ALL,
                HelperData::RULE_DESTINATION_STATUS => $dstStatus,
                HelperData::RULE_CAPTURE_MODE => $captureMode,
                HelperData::RULE_EMAIL => '',
            ]]);
        
        $orderCollectionMock = $this->getMockBuilder(OrderCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->orderCollectionFactoryMock->expects(self::once())
            ->method('create')
            ->willReturn($orderCollectionMock);
        
        $orderCollectionMock->expects(self::exactly(2))
            ->method('addFieldToFilter')
            ->willReturn($orderCollectionMock);
        
        $orders = [
            $this->getOrderMock(1, 'paypal'),
            $this->getOrderMock(2, 'paypal_express'),
            $this->getOrderMock(3, 'braintree'),
            $this->getOrderMock(4, 'braintree'),
            $this->getOrderMock(5, 'aune_stripe'),
        ];
        
        $orderCollectionMock->expects(self::once())
            ->method('getIterator')
            ->willReturn(new ArrayIterator($orders));
        
        $items = [];
        foreach ($orders as $order) {
            $itemMock = $this->createMock(InvoiceProcessItemInterface::class);
            
            $itemMock->expects(self::once())
                ->method('setOrder')
                ->with($order)
                ->willReturn($itemMock);
            
            $itemMock->expects(self::once())
                ->method('setDestinationStatus')
                ->with($dstStatus)
                ->willReturn($itemMock);
            
            $itemMock->expects(self::once())
                ->method('setCaptureMode')
                ->with($captureMode)
                ->willReturn($itemMock);

            $itemMock->expects(self::once())
                ->method('setEmail')
                ->with('')
                ->willReturn($itemMock);
            
            $items[$order->getId()] = $itemMock;
        }
        
        $this->invoiceProcessItemFactoryMock->expects(self::exactly(count($items)))
            ->method('create')
            ->willReturnOnConsecutiveCalls(...$items);
        
        $this->assertEquals(
            $this->invoiceProcess->getItemsToProcess(),
            $items
        );
    }
    
    public function testGetItemsToProcessPaymentMethods()
    {
        $srcStatus = 'processing';
        $dstStatusPaypal = 'complete';
        $captureModePaypal = 'offline';
        $dstStatusBraintree = 'processing';
        $captureModeBraintree = 'online';
        
        $this->helperDataMock->expects(self::once())
            ->method('getProcessingRules')
            ->willReturn([[
                HelperData::RULE_SOURCE_STATUS => $srcStatus,
                HelperData::RULE_PAYMENT_METHOD => 'paypal',
                HelperData::RULE_DESTINATION_STATUS => $dstStatusPaypal,
                HelperData::RULE_CAPTURE_MODE => $captureModePaypal,
                HelperData::RULE_EMAIL => '',
            ], [
                HelperData::RULE_SOURCE_STATUS => $srcStatus,
                HelperData::RULE_PAYMENT_METHOD => 'braintree',
                HelperData::RULE_DESTINATION_STATUS => $dstStatusBraintree,
                HelperData::RULE_CAPTURE_MODE => $captureModeBraintree,
                HelperData::RULE_EMAIL => '',
            ]]);
        
        $paypalOrders = [
            $this->getOrderMock(1, 'paypal'),
        ];
        $braintreeOrders = [
            $this->getOrderMock(3, 'braintree'),
            $this->getOrderMock(4, 'braintree')
        ];
        $otherOrders = [
            $this->getOrderMock(2, 'paypal_express'),
            $this->getOrderMock(5, 'aune_stripe'),
        ];
        
        $allData = [
            [$dstStatusPaypal, $captureModePaypal, $paypalOrders],
            [$dstStatusBraintree, $captureModeBraintree, $braintreeOrders],
        ];
        
        $items = [];
        $orderCollectionMocks = [];
        
        foreach ($allData as $data) {
            $dstStatus = $data[0];
            $captureMode = $data[1];
            $orders = $data[2];
            
            $orderCollectionMock = $this->getMockBuilder(OrderCollection::class)
                ->disableOriginalConstructor()
                ->getMock();
            
            $filterCalls = new \stdClass();
            $filterCalls->count = 0;
            $orderCollectionMock->expects(self::exactly(2))
                ->method('addFieldToFilter')
                ->willReturnCallback(function ($field, $condition) use ($filterCalls, $srcStatus, $orderCollectionMock) {
                    $expected = [
                        ['status', ['eq' => $srcStatus]],
                        ['total_invoiced', ['null' => true]],
                    ];
                    self::assertSame($expected[$filterCalls->count++], [$field, $condition]);
                    return $orderCollectionMock;
                });
            
            $orderCollectionMock->expects(self::once())
                ->method('getIterator')
                ->willReturn(new ArrayIterator(array_merge($orders, $otherOrders)));
            
            $orderCollectionMocks []= $orderCollectionMock;
            
            foreach ($orders as $order) {
                $itemMock = $this->createMock(InvoiceProcessItemInterface::class);
                $itemMock->expects(self::once())
                    ->method('setOrder')
                    ->with($order)
                    ->willReturn($itemMock);
                
                $itemMock->expects(self::once())
                    ->method('setDestinationStatus')
                    ->with($dstStatus)
                    ->willReturn($itemMock);
                
                $itemMock->expects(self::once())
                    ->method('setCaptureMode')
                    ->with($captureMode)
                    ->willReturn($itemMock);

                $itemMock->expects(self::once())
                    ->method('setEmail')
                    ->with('')
                    ->willReturn($itemMock);
                
                $items[$order->getId()] = $itemMock;
            }
        }
        
        $this->orderCollectionFactoryMock->expects(self::exactly(count($orderCollectionMocks)))
            ->method('create')
            ->willReturnOnConsecutiveCalls(...$orderCollectionMocks);
        
        $this->invoiceProcessItemFactoryMock->expects(self::exactly(count($items)))
            ->method('create')
            ->willReturnOnConsecutiveCalls(...$items);
        
        $this->assertEquals(
            $this->invoiceProcess->getItemsToProcess(),
            $items
        );
    }
    
    /**
     * Returns new mock order with given payment method
     */
    private function getOrderMock(int $orderId, string $paymentMethod)
    {
        $methodInstanceMock = $this->createMock(\Magento\Payment\Model\MethodInterface::class);
        
        $methodInstanceMock->expects(self::any())
            ->method('getCode')
            ->willReturn($paymentMethod);
        
        $paymentMock = $this->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $paymentMock->expects(self::any())
            ->method('getMethodInstance')
            ->willReturn($methodInstanceMock);
        
        $orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $orderMock->expects(self::any())
            ->method('getId')
            ->willReturn($orderId);
        
        $orderMock->expects(self::any())
            ->method('getPayment')
            ->willReturn($paymentMock);
        
        return $orderMock;
    }
    
    public function testInvoiceOffline()
    {
        $status = 'complete';
        $captureMode = 'offline';
        
        $orderStatusCollectionMock = $this->getMockBuilder(OrderStatusCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $orderStatusCollectionMock->expects(self::once())
            ->method('joinStates')
            ->willReturn($orderStatusCollectionMock);
        
        $this->orderStatusCollectionFactoryMock->expects(self::once())
            ->method('create')
            ->willReturn($orderStatusCollectionMock);
        
        $statuses = [
            $this->getOrderStatusMock('processing', 'processing'),
            $this->getOrderStatusMock('pending', 'pending'),
            $this->getOrderStatusMock('complete', 'complete'),
            $this->getOrderStatusMock('closed', 'closed'),
        ];
        
        $orderStatusCollectionMock->expects(self::once())
            ->method('getIterator')
            ->willReturn(new ArrayIterator($statuses));
        
        $orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $orderMock->expects(self::once())
            ->method('setStatus')
            ->with($status)
            ->willReturn($orderMock);
        
        $orderMock->expects(self::once())
            ->method('setState')
            ->with($status)
            ->willReturn($orderMock);
        
        // Capture left the status untouched, so it is not reset
        $orderMock->method('getStatus')->willReturn($status);
        
        $itemMock = $this->createMock(InvoiceProcessItemInterface::class);
        
        $itemMock->expects(self::once())
            ->method('getOrder')
            ->willReturn($orderMock);
        
        // Read once to set the status and once to check capture did not overwrite it
        $itemMock->expects(self::exactly(2))
            ->method('getDestinationStatus')
            ->willReturn($status);
        
        $itemMock->expects(self::once())
            ->method('getCaptureMode')
            ->willReturn($captureMode);
        
        $invoiceMock = $this->getMockBuilder(OrderInvoice::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['register'])
            ->getMock();
        
        $invoiceServiceMock = $this->getMockBuilder(InvoiceService::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $invoiceServiceMock->expects(self::once())
            ->method('prepareInvoice')
            ->with($orderMock)
            ->willReturn($invoiceMock);
        
        $this->invoiceServiceFactoryMock->expects(self::once())
            ->method('create')
            ->willReturn($invoiceServiceMock);
        
        $invoiceMock->expects(self::once())
            ->method('register');
        
        $this->transactionMock->expects(self::exactly(2))
            ->method('addObject')
            ->willReturn($this->transactionMock);
        
        $this->transactionMock->expects(self::once())
            ->method('save');
        
        $this->invoiceProcess->invoice($itemMock);

        self::assertSame($captureMode, $invoiceMock->getData('requested_capture_case'));
    }
    
    public function testInvoiceOnline()
    {
        $status = 'complete';
        $captureMode = 'online';
        
        $orderStatusCollectionMock = $this->getMockBuilder(OrderStatusCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $orderStatusCollectionMock->expects(self::once())
            ->method('joinStates')
            ->willReturn($orderStatusCollectionMock);
        
        $this->orderStatusCollectionFactoryMock->expects(self::once())
            ->method('create')
            ->willReturn($orderStatusCollectionMock);
        
        $statuses = [
            $this->getOrderStatusMock('processing', 'processing'),
            $this->getOrderStatusMock('pending', 'pending'),
            $this->getOrderStatusMock('complete', 'complete'),
            $this->getOrderStatusMock('closed', 'closed'),
        ];
        
        $orderStatusCollectionMock->expects(self::once())
            ->method('getIterator')
            ->willReturn(new ArrayIterator($statuses));
        
        $orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $orderMock->expects(self::once())
            ->method('setStatus')
            ->with($status)
            ->willReturn($orderMock);
        
        $orderMock->expects(self::once())
            ->method('setState')
            ->with($status)
            ->willReturn($orderMock);
        
        // Capture left the status untouched, so it is not reset
        $orderMock->method('getStatus')->willReturn($status);
        
        $itemMock = $this->createMock(InvoiceProcessItemInterface::class);
        
        $itemMock->expects(self::once())
            ->method('getOrder')
            ->willReturn($orderMock);
        
        // Read once to set the status and once to check capture did not overwrite it
        $itemMock->expects(self::exactly(2))
            ->method('getDestinationStatus')
            ->willReturn($status);
        
        $itemMock->expects(self::once())
            ->method('getCaptureMode')
            ->willReturn($captureMode);
        
        $invoiceMock = $this->getMockBuilder(OrderInvoice::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['register'])
            ->getMock();
        
        $invoiceServiceMock = $this->getMockBuilder(InvoiceService::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $invoiceServiceMock->expects(self::once())
            ->method('prepareInvoice')
            ->with($orderMock)
            ->willReturn($invoiceMock);
        
        $this->invoiceServiceFactoryMock->expects(self::once())
            ->method('create')
            ->willReturn($invoiceServiceMock);
        
        $invoiceMock->expects(self::once())
            ->method('register');
        
        $this->transactionMock->expects(self::exactly(2))
            ->method('addObject')
            ->willReturn($this->transactionMock);
        
        $this->transactionMock->expects(self::once())
            ->method('save');
        
        $this->invoiceProcess->invoice($itemMock);

        self::assertSame($captureMode, $invoiceMock->getData('requested_capture_case'));
    }
    
    /**
     * Returns a status object (getStatus/getState are magic getters) with the given status/state pair
     */
    private function getOrderStatusMock(string $status, string $state)
    {
        return new \Magento\Framework\DataObject(['status' => $status, 'state' => $state]);
    }
}
