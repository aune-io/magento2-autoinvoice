<?php

namespace Aune\AutoInvoice\Api\Data;

interface InvoiceProcessItemInterface
{
    public const KEY_ORDER = 'order';
    public const KEY_DESTINATION_STATUS = 'destination_status';
    public const KEY_CAPTURE_MODE = 'capture_mode';
    public const KEY_EMAIL = 'email';

    /**
     * Returns the order to invoice
     *
     * @returns \Magento\Sales\Api\Data\OrderInterface
     */
    public function getOrder();

    /**
     * Sets the order to invoice
     *
     * @param OrderInterface $order
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
     * @param string $status
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
     * @param string $captureMode
     *
     * @returns $this
     */
    public function setCaptureMode(string $captureMode);

    /**
     * Returns E-Mail settings
     *
     * @returns string
     */
    public function getEmail();

    /**
     * Sets E-Mail settings
     *
     * @param string $email
     *
     * @returns $this
     */
    public function setEmail(string $email);
}
