<?php

namespace Aune\AutoInvoice\Api;

interface InvoiceProcessInterface
{
    /**
     * Returns a list of orders that should be invoiced.
     * 
     * @returns \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    public function getOrdersToInvoice();
    
    /**
     * Invoice order
     * 
     * @param \Magento\Sales\Model\Order $order
     */
    public function invoice(\Magento\Sales\Model\Order $order);
}
