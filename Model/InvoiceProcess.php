<?php

namespace Aune\AutoInvoice\Model;

use Magento\Framework\DB\TransactionFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice as OrderInvoice;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory as OrderStatusCollectionFactory;
use Magento\Sales\Model\Service\InvoiceServiceFactory;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Psr\Log\LoggerInterface;

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
     * @var TransactionFactory
     */
    private $transactionFactory;

    /**
     * @var InvoiceServiceFactory
     */
    private $invoiceServiceFactory;

    /**
     * @var InvoiceSender
     */
    private $invoiceSender;

    /**
     * @var array
     */
    private $orderStatusToStateMap;

    /**
     * @var Logger
     */
    private $_logger;

    /**
     * @param HelperData $helperData
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param OrderStatusCollectionFactory $orderStatusCollectionFactory
     * @param InvoiceProcessItemInterfaceFactory $invoiceProcessItemFactory
     * @param TransactionFactory $transactionFactory
     * @param InvoiceServiceFactory $invoiceServiceFactory
     * @param InvoiceSender $invoiceSender
     */
    public function __construct(
        HelperData                         $helperData,
        OrderCollectionFactory             $orderCollectionFactory,
        OrderStatusCollectionFactory       $orderStatusCollectionFactory,
        InvoiceProcessItemInterfaceFactory $invoiceProcessItemFactory,
        TransactionFactory                 $transactionFactory,
        InvoiceServiceFactory              $invoiceServiceFactory,
        InvoiceSender                      $invoiceSender,
        LoggerInterface                    $logger
    ) {
        $this->helperData = $helperData;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->orderStatusCollectionFactory = $orderStatusCollectionFactory;
        $this->invoiceProcessItemFactory = $invoiceProcessItemFactory;
        $this->transactionFactory = $transactionFactory;
        $this->invoiceServiceFactory = $invoiceServiceFactory;
        $this->invoiceSender = $invoiceSender;
        $this->_logger = $logger;
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
                    ->setDestinationStatus($rule[HelperData::RULE_DESTINATION_STATUS])
                    ->setCaptureMode($rule[HelperData::RULE_CAPTURE_MODE])
                    ->setEmail($rule[HelperData::RULE_EMAIL]);
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
        $invoice->setRequestedCaptureCase($item->getCaptureMode());
        $invoice->register();

        if ($order->getStatus() !== $item->getDestinationStatus()) {
            // Capture may overwrite order status, reset it
            $order->setStatus($item->getDestinationStatus());
            if ($state) {
                $order->setState($state);
            }
        }

        $transactionSave = $this->transactionFactory->create()
            ->addObject($invoice)
            ->addObject($order);

        $transactionSave->save();
        $email = $item->getEmail();
        if ($email=='true') {
          try {
            $this->invoiceSender->send($invoice);
            $invoice->setEmailSent(true);
          } catch (\Exception $e) {
            $this->_logger->debug("Error while sending invoice-E-Mail: ".$e);
          }
        }
    }
}
