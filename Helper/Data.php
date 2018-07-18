<?php

namespace Aune\AutoInvoice\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Data
{
    const XML_PATH_AUTO_INVOICE_COMPLETE = 'sales/general/auto_invoice_complete';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    
    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Return whether the orders should be automatically processed via cron
     */
    public function isCronEnabled()
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_AUTO_INVOICE_COMPLETE);
    }
}
