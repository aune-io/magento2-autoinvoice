<?php

namespace Aune\AutoInvoice\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;
use Magento\Sales\Model\Order\Config;

/**
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class Status extends Select
{
    /**
     * @var string[]
     */
    private $stateStatuses = [
        \Magento\Sales\Model\Order::STATE_NEW,
        \Magento\Sales\Model\Order::STATE_PROCESSING,
        \Magento\Sales\Model\Order::STATE_COMPLETE,
        \Magento\Sales\Model\Order::STATE_CLOSED,
        \Magento\Sales\Model\Order::STATE_CANCELED,
        \Magento\Sales\Model\Order::STATE_HOLDED,
    ];
    
    /**
     * @var Config
     */
    private $orderConfig;

    /**
     * @param Context $context
     * @param Config $orderConfig
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $orderConfig,
        array $data = []
    ) {
        $this->orderConfig = $orderConfig;
        
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
            $statuses = $this->stateStatuses
                ? $this->orderConfig->getStateStatuses($this->stateStatuses)
                : $this->orderConfig->getStatuses();
            
            $this->setOptions($statuses);
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
