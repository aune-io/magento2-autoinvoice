<?php

namespace Aune\AutoInvoice\Model\Adminhtml\System\Config;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Math\Random;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Aune\AutoInvoice\Helper\Data as HelperData;

class ProcessingRule extends Value
{
    /**
     * @var \Magento\Framework\Math\Random
     */
    private $mathRandom;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $serializer;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\Math\Random $mathRandom
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @param \Magento\Framework\Serialize\Serializer\Json $serializer
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        Random $mathRandom,
        Json $serializer,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->mathRandom = $mathRandom;
        $this->serializer = $serializer;
        
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Prepare data before save
     *
     * @return $this
     */
    public function beforeSave()
    {
        $value = $this->getValue();
        $result = [];
        
        foreach ($value as $data) {
            if (empty($data[HelperData::RULE_SOURCE_STATUS])
                || empty($data[HelperData::RULE_PAYMENT_METHOD])
                || empty($data[HelperData::RULE_DESTINATION_STATUS])) {
                
                continue;
            }
            
            $key = implode(HelperData::RULE_KEY_SEPARATOR, [
                $data[HelperData::RULE_SOURCE_STATUS],
                $data[HelperData::RULE_PAYMENT_METHOD],
            ]);
            $result[$key] = $data[HelperData::RULE_DESTINATION_STATUS];
        }
        
        $this->setValue($this->serializer->serialize($result));
        
        return $this;
    }
    
    /**
     * Process data after load
     *
     * @return $this
     */
    public function afterLoad()
    {
        if ($this->getValue()) {
            $value = $this->serializer->unserialize($this->getValue());
            if (is_array($value)) {
                $this->setValue($this->encodeArrayFieldValue($value));
            }
        }
        return $this;
    }
    
    /**
     * Encode value to be used in \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
     *
     * @param array $value
     * @return array
     */
    protected function encodeArrayFieldValue(array $value)
    {
        $result = [];
        foreach ($value as $key => $dstStatus) {
            $parts = explode(HelperData::RULE_KEY_SEPARATOR, $key);
            $id = $this->mathRandom->getUniqueHash('_');
            
            $result[$id] = [
                HelperData::RULE_SOURCE_STATUS => $parts[0],
                HelperData::RULE_PAYMENT_METHOD => $parts[1],
                HelperData::RULE_DESTINATION_STATUS => $dstStatus,
            ];
        }
        return $result;
    }
}
