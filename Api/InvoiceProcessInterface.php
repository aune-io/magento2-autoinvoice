<?php

namespace Aune\AutoInvoice\Api;

/**
 * @api
 */
interface InvoiceProcessInterface
{
    /**
     * Returns a list of items to process.
     * Every item consists of an order, and a destination status.
     * 
     * @returns \Aune\AutoInvoice\Api\Data\InvoiceProcessItemInterface
     */
    public function getItemsToProcess();
    
    /**
     * Invoice order
     * 
     * @param \Aune\AutoInvoice\Api\Data\InvoiceProcessItemInterface $item
     */
    public function invoice(\Aune\AutoInvoice\Api\Data\InvoiceProcessItemInterface $item);
}
