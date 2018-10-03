<?php

namespace Aune\AutoInvoice\Test\Unit\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\Serializer\Json;
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
            $this->scopeConfigMock,
            new Json()
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
            ['key' => HelperData::XML_PATH_PROCESSING_RULES, 'isFlag' => false, 'method' => 'getProcessingRules', 'in' => '', 'out' => []],
        ];
    }
    
    /**
     * @dataProvider getProcessingRulesDataProvider
     */
    public function testGetProcessingRules($in, $out)
    {
        $this->scopeConfigMock->expects(self::once())
            ->method('getValue')
            ->with(HelperData::XML_PATH_PROCESSING_RULES)
            ->willReturn($in);
        
        self::assertEquals(
            $out,
            $this->helperData->getProcessingRules()
        );
    }
    
    /**
     * @return array
     */
    public function getProcessingRulesDataProvider()
    {
        return [[
            'in' => '{"pending|*":"complete"}',
            'out' => [[
                HelperData::RULE_SOURCE_STATUS => 'pending',
                HelperData::RULE_PAYMENT_METHOD => HelperData::RULE_PAYMENT_METHOD_ALL,
                HelperData::RULE_DESTINATION_STATUS => 'complete',
            ]],
        ], [
            'in' => '{"pending|*":"complete","pending|free":"processing"}',
            'out' => [[
                HelperData::RULE_SOURCE_STATUS => 'pending',
                HelperData::RULE_PAYMENT_METHOD => HelperData::RULE_PAYMENT_METHOD_ALL,
                HelperData::RULE_DESTINATION_STATUS => 'complete',
            ], [
                HelperData::RULE_SOURCE_STATUS => 'pending',
                HelperData::RULE_PAYMENT_METHOD => 'free',
                HelperData::RULE_DESTINATION_STATUS => 'processing',
            ]],
        ], [
            'in' => '{"pending|*":"complete","processing|*":"complete","pending|free":"processing"}',
            'out' => [[
                HelperData::RULE_SOURCE_STATUS => 'pending',
                HelperData::RULE_PAYMENT_METHOD => HelperData::RULE_PAYMENT_METHOD_ALL,
                HelperData::RULE_DESTINATION_STATUS => 'complete',
            ], [
                HelperData::RULE_SOURCE_STATUS => 'processing',
                HelperData::RULE_PAYMENT_METHOD => HelperData::RULE_PAYMENT_METHOD_ALL,
                HelperData::RULE_DESTINATION_STATUS => 'complete',
            ], [
                HelperData::RULE_SOURCE_STATUS => 'pending',
                HelperData::RULE_PAYMENT_METHOD => 'free',
                HelperData::RULE_DESTINATION_STATUS => 'processing',
            ]],
        ]];
    }
}
