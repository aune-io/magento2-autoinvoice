<?php

namespace Aune\AutoInvoice\Model;

use Magento\Framework\DB\Transaction;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice as OrderInvoice;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory as OrderStatusCollectionFactory;
use Magento\Sales\Model\Service\InvoiceServiceFactory;

use Aune\AutoInvoice\Api\InvoiceProcessInterface;
use Aune\AutoInvoice\Api\Data\InvoiceProcessItemInterface;
use Aune\AutoInvoice\Api\Data\InvoiceProcessItemInterfaceFactory;
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
     * @var OrderStatusCollectionFactory
     */
    private $orderStatusCollectionFactory;
    
    /**
     * @var InvoiceProcessItemInterfaceFactory
     */
    private $invoiceProcessItemFactory;
    
    /**
     * @var Transaction
     */
    private $transaction;
    
    /**
     * @var InvoiceServiceFactory
     */
    private $invoiceServiceFactory;
    
    /**
     * @var array
     */
    private $orderStatusToStateMap;
    
    /**
     * @param HelperData $helperData
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param OrderStatusCollectionFactory $orderStatusCollectionFactory
     * @param InvoiceProcessItemInterfaceFactory $invoiceProcessItemFactory
     * @param Transaction $transaction
     * @param InvoiceServiceFactory $invoiceServiceFactory
     */
    public function __construct(
        HelperData $helperData,
        OrderCollectionFactory $orderCollectionFactory,
        OrderStatusCollectionFactory $orderStatusCollectionFactory,
        InvoiceProcessItemInterfaceFactory $invoiceProcessItemFactory,
        Transaction $transaction,
        InvoiceServiceFactory $invoiceServiceFactory
    ) {
        $this->helperData = $helperData;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->orderStatusCollectionFactory = $orderStatusCollectionFactory;
        $this->invoiceProcessItemFactory = $invoiceProcessItemFactory;
        $this->transaction = $transaction;
        $this->invoiceServiceFactory = $invoiceServiceFactory;
    }
    
    /**
     * @inheritdoc
     */
    public function getItemsToProcess()
    {
        $items = [];
        $rules = $this->helperData->getProcessingRules();
        
        foreach ($rules as $rule) {
            $collection = $this->orderCollectionFactory->create()
                ->addFieldToFilter('status', ['eq' => $rule[HelperData::RULE_SOURCE_STATUS]])
                ->addFieldToFilter('total_invoiced', ['null' => true]);
            
            foreach ($collection as $order) {
                if ($rule[HelperData::RULE_PAYMENT_METHOD] != HelperData::RULE_PAYMENT_METHOD_ALL
                    && $rule[HelperData::RULE_PAYMENT_METHOD] != $this->getPaymentMethodCode($order)) {
                    
                    continue;
                }
                
                $items[$order->getId()] = $this->invoiceProcessItemFactory->create()
                    ->setOrder($order)
                    ->setDestinationStatus($rule[HelperData::RULE_DESTINATION_STATUS]);
            }
        }
        
        return $items;
    }
    
    /**
     * Returns payment method code of the given order
     */
    private function getPaymentMethodCode(Order $order)
    {
        try {
            return $order->getPayment()->getMethodInstance()->getCode();
        } catch (\Exception $ex) {
            return '';
        }
    }
    
    /**
     * Returns the order status to state map
     */
    private function getOrderStatusToStateMap()
    {
        if (!is_null($this->orderStatusToStateMap)) {
            return $this->orderStatusToStateMap;
        }
        
        $collection = $this->orderStatusCollectionFactory->create()
            ->joinStates();
        
        $this->orderStatusToStateMap = [];
        foreach ($collection as $status) {
            $this->orderStatusToStateMap[$status->getStatus()] = $status->getState();
        }
        
        return $this->orderStatusToStateMap;
    }
    
    /**
     * Return the order state given a status
     */
    private function getOrderStateByStatus(string $status)
    {
        $map = $this->getOrderStatusToStateMap();
        return empty($map[$status]) ? false : $map[$status];
    }
    
    /**
     * @inheritdoc
     */
    public function invoice(InvoiceProcessItemInterface $item)
    {
        $order = $item->getOrder();
        
        $status = $item->getDestinationStatus();
        $order->setStatus($status);

        $state = $this->getOrderStateByStatus($status);
        if ($state) {
            $order->setState($state);
        }
        
        $invoice = $this->invoiceServiceFactory->create()
            ->prepareInvoice($order);
        $invoice->setRequestedCaptureCase(OrderInvoice::CAPTURE_OFFLINE);
        $invoice->register();
        
        $transactionSave = $this->transaction
            ->addObject($invoice)
            ->addObject($order);
        
        $transactionSave->save();
    }
}
