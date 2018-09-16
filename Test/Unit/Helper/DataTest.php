<?php

namespace Aune\AutoInvoice\Test\Unit\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Aune\AutoInvoice\Helper\Data as HelperData;

/**
 * @SuppressWarnings(PHPMD.ShortVariable)
 */
class DataTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ScopeConfigInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeConfigMock;
    
    /**
     * @var HelperData
     */
    private $helperData;

    protected function setUp()
    {
        $this->scopeConfigMock = $this->getMockForAbstractClass(ScopeConfigInterface::class);
        
        $this->helperData = new HelperData(
            $this->scopeConfigMock
        );
    }

    /**
     * @dataProvider getConfigDataProvider
     */
    public function testGetConfigValue($key, $isFlag, $method, $in, $out)
    {
        $this->scopeConfigMock->expects(self::once())
            ->method($isFlag ? 'isSetFlag' : 'getValue')
            ->with($key)
            ->willReturn($in);
        
        self::assertEquals(
            $out,
            $this->helperData->$method()
        );
    }
    
    /**
     * @return array
     */
    public function getConfigDataProvider()
    {
        return [
            ['key' => HelperData::XML_PATH_CRON_ENABLED, 'isFlag' => true, 'method' => 'isCronEnabled', 'in' => '1', 'out' => true],
            ['key' => HelperData::XML_PATH_CRON_ENABLED, 'isFlag' => true, 'method' => 'isCronEnabled', 'in' => '0', 'out' => false],
            ['key' => HelperData::XML_PATH_ORDER_STATUSES, 'isFlag' => false, 'method' => 'getOrderStatuses', 'in' => '', 'out' => []],
            ['key' => HelperData::XML_PATH_ORDER_STATUSES, 'isFlag' => false, 'method' => 'getOrderStatuses', 'in' => 'a,b', 'out' => ['a', 'b']],
        ];
    }
}
