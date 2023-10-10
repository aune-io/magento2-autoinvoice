<?php

namespace Aune\AutoInvoice\Test\Unit\Model;

use Magento\Sales\Model\Order;
use Aune\AutoInvoice\Api\Data\InvoiceProcessItemInterface;
use Aune\AutoInvoice\Model\InvoiceProcessItem;

class InvoiceProcessItemTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var InvoiceProcessItem
     */
    private $invoiceProcessItem;

    protected function setUp()
    {
        $this->invoiceProcessItem = new InvoiceProcessItem();
    }

    /**
     * Test class service contract
     */
    public function testServiceContract()
    {
        $this->assertInstanceOf(
            InvoiceProcessItemInterface::class,
            $this->invoiceProcessItem
        );
    }

    /**
     * @dataProvider getFieldsDataProvider
     */
    public function testGettersAndSetters($field, $getter, $setter, $value)
    {
        $this->assertEquals(
            $this->invoiceProcessItem->$setter($value),
            $this->invoiceProcessItem
        );

        $this->assertEquals(
            $this->invoiceProcessItem->getData($field),
            $value
        );

        $this->assertEquals(
            $this->invoiceProcessItem->$getter(),
            $value
        );
    }

    /**
     * @return array
     */
    public function getFieldsDataProvider()
    {
        $orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();

        return [
            ['field' => InvoiceProcessItemInterface::KEY_ORDER, 'getter' => 'getOrder', 'setter' => 'setOrder', 'value' => $orderMock],
            ['field' => InvoiceProcessItemInterface::KEY_DESTINATION_STATUS, 'getter' => 'getDestinationStatus', 'setter' => 'setDestinationStatus', 'value' => 'complete'],
        ];
    }
}
