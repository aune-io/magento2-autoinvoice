<?php

namespace Aune\AutoInvoice\Model;

use Magento\Framework\DB\Transaction;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice as OrderInvoice;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\Service\InvoiceService;

use Aune\AutoInvoice\Api\InvoiceProcessInterface;
use Aune\AutoInvoice\Helper\Data as HelperData;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class InvoiceProcess implements InvoiceProcessInterface
{
    /**
     * @var HelperData
     */
    private $helperData;
    
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
     * @param HelperData $helperData
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param Transaction $transaction
     * @param InvoiceService $invoiceService
     */
    public function __construct(
        HelperData $helperData,
        OrderCollectionFactory $orderCollectionFactory,
        Transaction $transaction,
        InvoiceService $invoiceService
    ) {
        $this->helperData = $helperData;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->transaction = $transaction;
        $this->invoiceService = $invoiceService;
    }
    
    /**
     * @inheritdoc
     */
    public function getOrdersToInvoice()
    {
        $statuses = $this->helperData->getOrderStatuses();
        
        return $this->orderCollectionFactory->create()
            ->addFieldToFilter('status', ['in' => $statuses])
            ->addFieldToFilter('total_invoiced', ['null' => true]);
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
