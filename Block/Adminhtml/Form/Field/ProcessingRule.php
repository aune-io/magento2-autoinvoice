<?php

namespace Aune\AutoInvoice\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;

/**
 * @codeCoverageIgnore
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.CamelCaseMethodName)
 */
class ProcessingRule extends AbstractFieldArray
{
    /**
     * @var Status
     */
    private $srcStatusRenderer = null;

    /**
     * @var Status
     */
    private $dstStatusRenderer = null;

    /**
     * @var PaymentMethod
     */
    private $paymentMethodRenderer = null;

    /**
     * @var CaptureMode
     */
    private $captureModeRenderer = null;


    /**
     * @var Email
     */
    private $emailRenderer = null;

    /**
     * Returns renderer for source status element
     */
    protected function getSrcStatusRenderer()
    {
        if (!$this->srcStatusRenderer) {
            $this->srcStatusRenderer = $this->getLayout()->createBlock(
                Status::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }

        return $this->srcStatusRenderer;
    }

    /**
     * Returns renderer for destination status element
     */
    protected function getDstStatusRenderer()
    {
        if (!$this->dstStatusRenderer) {
            $this->dstStatusRenderer = $this->getLayout()->createBlock(
                Status::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }

        return $this->dstStatusRenderer;
    }

    /**
     * Returns renderer for payment method
     */
    protected function getPaymentMethodRenderer()
    {
        if (!$this->paymentMethodRenderer) {
            $this->paymentMethodRenderer = $this->getLayout()->createBlock(
                PaymentMethod::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->paymentMethodRenderer;
    }

    /**
     * Returns renderer for capture mode
     */
    protected function getCaptureModeRenderer()
    {
        if (!$this->captureModeRenderer) {
            $this->captureModeRenderer = $this->getLayout()->createBlock(
                CaptureMode::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->captureModeRenderer;
    }

    /**
     * Returns renderer for E-Mail sending
     */
    protected function getEmailRenderer()
    {
        if (!$this->emailRenderer) {
            $this->emailRenderer = $this->getLayout()->createBlock(
                Email::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->emailRenderer;
    }
    /**
     * Prepare to render
     * @return void
     */
    protected function _prepareToRender()
    {
        $this->addColumn(
            'src_status',
            [
                'label'     => __('Source Status'),
                'renderer'  => $this->getSrcStatusRenderer(),
            ]
        );
        $this->addColumn(
            'payment_method',
            [
                'label' => __('Payment Method'),
                'renderer'  => $this->getPaymentMethodRenderer(),
            ]
        );
        $this->addColumn(
            'dst_status',
            [
                'label'     => __('Destination Status'),
                'renderer'  => $this->getDstStatusRenderer(),
            ]
        );
        $this->addColumn(
            'capture_mode',
            [
                'label'     => __('Capture Mode'),
                'renderer'  => $this->getCaptureModeRenderer(),
            ]
        );
        $this->addColumn(
            'send_email',
            [
                'label'     => __('Send E-Mail'),
                'renderer'  => $this->getEmailRenderer(),
            ]
        );

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Rule');
    }

    /**
     * Prepare existing row data object
     *
     * @param DataObject $row
     * @return void
     */
    protected function _prepareArrayRow(DataObject $row)
    {
        $srcStatus = $row->getSrcStatus();
        $dstStatus = $row->getDstStatus();
        $paymentMethod = $row->getPaymentMethod();
        $captureMode = $row->getCaptureMode();

        $options = [];
        if ($srcStatus) {
            $options['option_' . $this->getSrcStatusRenderer()->calcOptionHash($srcStatus)]
                = 'selected="selected"';

            $options['option_' . $this->getDstStatusRenderer()->calcOptionHash($dstStatus)]
                = 'selected="selected"';

            $options['option_' . $this->getPaymentMethodRenderer()->calcOptionHash($paymentMethod)]
                = 'selected="selected"';

            $options['option_' . $this->getCaptureModeRenderer()->calcOptionHash($captureMode)]
                = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }
}
