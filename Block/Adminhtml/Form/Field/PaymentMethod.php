<?php

namespace Aune\AutoInvoice\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;
use Magento\Payment\Model\Config as PaymentConfig;
use Aune\AutoInvoice\Helper\Data as HelperData;

class PaymentMethod extends Select
{
    /**
     * @var PaymentConfig
     */
    private $paymentConfig;
    
    /**
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        PaymentConfig $paymentConfig,
        array $data = []
    ) {
        $this->paymentConfig = $paymentConfig;
        
        parent::__construct($context, $data);
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->getOptions()) {
            $options = [
                ['value' => HelperData::RULE_PAYMENT_METHOD_ALL, 'label' => __('Any')]
            ];
            
            $paymentMethods = $this->paymentConfig->getActiveMethods();
            foreach ($paymentMethods as $code => $model) {
                $options []= [
                    'value' => $code,
                    'label' => $model->getTitle() ?: $code,
                ];
            }
            
            $this->setOptions($options);
        }
        
        return parent::_toHtml();
    }

    /**
     * Sets name for input element
     *
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }
}
