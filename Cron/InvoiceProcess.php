<?php

namespace Aune\AutoInvoice\Cron;

use Psr\Log\LoggerInterface;
use Aune\AutoInvoice\Api\InvoiceProcessInterface;
use Aune\AutoInvoice\Helper\Data as HelperData;

class InvoiceProcess
{
    /**
     * @var HelperData
     */
    private $helperData;
    
    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * @var InvoiceProcessInterface
     */
    private $invoiceProcess;
    
    /**
     * @param HelperData $helperData
     * @param LoggerInterface $logger
     * @param InvoiceProcessInterface $invoiceProcess
     */
    public function __construct(
        HelperData $helperData,
        LoggerInterface $logger,
    	InvoiceProcessInterface $invoiceProcess
    ) {
        $this->helperData = $helperData;
        $this->logger = $logger;
        $this->invoiceProcess = $invoiceProcess;
    }
    
    /**
     * Process completed orders with no invoice, if cron is enabled
     */
    public function execute()
    {
        if (!$this->helperData->isCronEnabled()) {
            return;
        }
        
        $this->logger->info('Starting auto invoice procedure.');
        $items = $this->invoiceProcess->getItemsToProcess();
        
        foreach ($items as $item) {
            try {
                
                $order = $item->getOrder();
                $this->logger->info(sprintf(
    				'Invoicing order #%s',
    				$order->getIncrementId()
    			));
    			$this->invoiceProcess->invoice($item);
			    
        	} catch (\Exception $ex) {
        		$this->logger->critical($ex->getMessage());
        	}
        }
        
        $this->logger->info('Auto invoice procedure completed.');
    }
}
