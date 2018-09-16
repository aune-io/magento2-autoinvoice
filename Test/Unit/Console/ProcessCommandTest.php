<?php

namespace Aune\AutoInvoice\Test\Unit\Console;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\State;
use Magento\Sales\Model\Order;
use Aune\AutoInvoice\Api\InvoiceProcessInterface;
use Aune\AutoInvoice\Console\ProcessCommand;

/**
 * @SuppressWarnings(PHPMD.ShortVariable)
 */
class ProcessCommandTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var State|PHPUnit_Framework_MockObject_MockObject
     */
    private $stateMock;
    
    /**
     * @var LoggerInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;
    
    /**
     * @var InvoiceProcessInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $invoiceProcessMock;
    
    /**
     * @var ProcessCommand
     */
    private $processCommand;
    
    protected function setUp()
    {
        $this->stateMock = $this->getMockBuilder(State::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->invoiceProcessMock = $this->createMock(InvoiceProcessInterface::class);
        
        $this->processCommand = new ProcessCommand(
            $this->stateMock,
            $this->loggerMock,
            $this->invoiceProcessMock
        );
    }
    
    /**
     * @covers \Aune\AutoInvoice\Console\ProcessCommand::configure
     */
    public function testConfigure()
    {
        $this->assertEquals(
            $this->processCommand->getName(),
            ProcessCommand::COMMAND_NAME
        );
        
        $this->assertEquals(
            $this->processCommand->getDescription(),
            ProcessCommand::COMMAND_DESCRIPTION
        );
    }
    
    /**
     * @covers \Aune\AutoInvoice\Console\ProcessCommand::execute
     */
    public function testExecuteDryRun()
    {
        $inputMock = $this->getMockBuilder(InputInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $inputMock->expects(self::exactly(1))
            ->method('getOption')
            ->with(ProcessCommand::OPTION_DRY_RUN)
            ->willReturn(true);
        
        $outputMock = $this->getMockBuilder(OutputInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $n = 10;
        $orderMocks = [];
        
        for ($i=0; $i<$n; $i++) {
            $orderMock = $this->getMockBuilder(Order::class)
                ->disableOriginalConstructor()
                ->getMock();
            
            $orderMocks []= $orderMock;
        }
        
        $this->invoiceProcessMock->expects(self::once())
            ->method('getOrdersToInvoice')
            ->willReturn($orderMocks);
        
        $this->invoiceProcessMock->expects(self::exactly(0))
            ->method('invoice');
        
        $this->processCommand->run($inputMock, $outputMock);
    }
    
    /**
     * @covers \Aune\AutoInvoice\Console\ProcessCommand::execute
     */
    public function testExecute()
    {
        $inputMock = $this->getMockBuilder(InputInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $inputMock->expects(self::exactly(1))
            ->method('getOption')
            ->with(ProcessCommand::OPTION_DRY_RUN)
            ->willReturn(false);
        
        $outputMock = $this->getMockBuilder(OutputInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $n = 10;
        $orderMocks = [];
        
        for ($i=0; $i<$n; $i++) {
            $orderMock = $this->getMockBuilder(Order::class)
                ->disableOriginalConstructor()
                ->getMock();
            
            $orderMocks []= $orderMock;
        }
        
        $this->invoiceProcessMock->expects(self::once())
            ->method('getOrdersToInvoice')
            ->willReturn($orderMocks);
        
        $this->invoiceProcessMock->expects(self::exactly(count($orderMocks)))
            ->method('invoice');
        
        $this->processCommand->run($inputMock, $outputMock);
    }
}
