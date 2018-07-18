<?php

namespace Aune\AutoInvoice\Model;

use Magento\Framework\DB\Transaction;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice as OrderInvoice;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\Service\InvoiceService;
use Aune\AutoInvoice\Api\InvoiceProcessInterface;

class InvoiceProcess implements InvoiceProcessInterface
{
    /**
     * @var OrderCollectionFactory
     */
    private $orderCollectionFactory;
    
    /**
     * @var Transaction
     */
    private $transaction;
    
    /**
     * @var InvoiceService
     */
    private $invoiceService;
    
    /**
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param Transaction $transaction
     * @param InvoiceService $invoiceService
     */
    public function __construct(
        OrderCollectionFactory $orderCollectionFactory,
        Transaction $transaction,
        InvoiceService $invoiceService
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->transaction = $transaction;
        $this->invoiceService = $invoiceService;
    }
    
    /**
     * @inheritdoc
     */
    public function getOrdersToInvoice()
    {
        return $this->orderCollectionFactory->create()
            ->addFieldToFilter('status', ['eq' => Order::STATE_COMPLETE])
            ->addFieldToFilter('total_invoiced', ['eq' => 0]);
    }
    
    /**
     * @inheritdoc
     */
    public function invoice(Order $order)
    {
        $invoice = $this->invoiceService->prepareInvoice($order);
        $invoice->setRequestedCaptureCase(OrderInvoice::CAPTURE_OFFLINE);
        $invoice->register();
        
        $transactionSave = $this->transaction
            ->addObject($invoice)
            ->addObject($order);

        $transactionSave->save();
    }
}
