<?php

namespace Aune\AutoInvoice\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;
use Magento\Payment\Helper\Data as PaymentHelper;
use Aune\AutoInvoice\Helper\Data as HelperData;

/**
 * @codeCoverageIgnore
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class PaymentMethod extends Select
{
    /**
     * @var PaymentConfig
     */
    private $paymentHelper;

    /**
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        PaymentHelper $paymentHelper,
        array $data = []
    ) {
        $this->paymentHelper = $paymentHelper;

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

            $options = array_merge($options, $this->paymentHelper->getPaymentMethodList(true, true));

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
