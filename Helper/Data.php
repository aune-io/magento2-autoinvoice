<?php

namespace Aune\AutoInvoice\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Data
{
    const XML_PATH_CRON_ENABLED = 'sales/autoinvoice/cron_active';
    const XML_PATH_ORDER_STATUSES = 'sales/autoinvoice/statuses';

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
        return (bool) $this->scopeConfig->isSetFlag(self::XML_PATH_CRON_ENABLED);
    }
    
    /**
     * Return statuses to process
     */
    public function getOrderStatuses()
    {
        $value = $this->scopeConfig->getValue(self::XML_PATH_ORDER_STATUSES);
        
        return $value ? explode(',', $value) : [];
    }
}
