<?php

namespace Aune\AutoInvoice\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Model\Order\Invoice;

class Data
{
    const XML_PATH_CRON_ENABLED = 'sales/autoinvoice/cron_active';
    const XML_PATH_PROCESSING_RULES = 'sales/autoinvoice/processing_rules';

    const RULE_SOURCE_STATUS = 'src_status';
    const RULE_DESTINATION_STATUS = 'dst_status';
    const RULE_PAYMENT_METHOD = 'payment_method';
    const RULE_CAPTURE_MODE = 'capture_mode';
    const RULE_KEY_SEPARATOR = '|';
    const RULE_PAYMENT_METHOD_ALL = '*';
    const RULE_EMAIL = 'email';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Json $serializer
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Json $serializer
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->serializer = $serializer;
    }

    /**
     * Return whether the orders should be automatically processed via cron
     */
    public function isCronEnabled()
    {
        return (bool) $this->scopeConfig->isSetFlag(self::XML_PATH_CRON_ENABLED);
    }

    /**
     * Return processing rules
     */
    public function getProcessingRules()
    {
        $value = $this->scopeConfig->getValue(self::XML_PATH_PROCESSING_RULES);
        $value = $value ? $this->serializer->unserialize($value) : [];

        $rules = [];
        foreach ($value as $key => $value) {
            $parts = explode(self::RULE_KEY_SEPARATOR, $key);

            if (is_array($value)) {
                $dstStatus = $value[self::RULE_DESTINATION_STATUS];
                $captureMode = $value[self::RULE_CAPTURE_MODE];
                $email = $value[self::RULE_EMAIL];
            } else {
                $dstStatus = $value;
                $captureMode = Invoice::CAPTURE_OFFLINE;
                $email = '';
            }

            $rules []= [
                self::RULE_SOURCE_STATUS => $parts[0],
                self::RULE_PAYMENT_METHOD => $parts[1],
                self::RULE_DESTINATION_STATUS => $dstStatus,
                self::RULE_CAPTURE_MODE => $captureMode,
                self::RULE_EMAIL => $email,
            ];
        }

        return $rules;
    }
}
