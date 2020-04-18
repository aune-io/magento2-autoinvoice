<?php

namespace Aune\AutoInvoice\Api\Data;

interface InvoiceProcessItemInterface
{
    const KEY_ORDER = 'order';
    const KEY_DESTINATION_STATUS = 'destination_status';
    const KEY_CAPTURE_MODE = 'capture_mode';
    
    /**
     * Returns the order to invoice
     * 
     * @returns \Magento\Sales\Api\Data\OrderInterface
     */
    public function getOrder();
    
    /**
     * Sets the order to invoice
     * 
     * @returns $this
     */
    public function setOrder(\Magento\Sales\Api\Data\OrderInterface $order);
    
    /**
     * Returns the destination status
     * 
     * @returns string
     */
    public function getDestinationStatus();
    
    /**
     * Sets the destination status
     * 
     * @returns $this
     */
    public function setDestinationStatus(string $status);
    
    /**
     * Returns the capture mode
     * 
     * @returns string
     */
    public function getCaptureMode();
    
    /**
     * Sets the capture mode
     * 
     * @returns $this
     */
    public function setCaptureMode(string $captureMode);
}
