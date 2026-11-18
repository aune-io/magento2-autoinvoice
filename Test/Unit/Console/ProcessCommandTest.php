<?php

namespace Aune\AutoInvoice\Test\Unit\Console;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\State;
use Magento\Sales\Model\Order;
use Aune\AutoInvoice\Api\Data\InvoiceProcessItemInterface;
use Aune\AutoInvoice\Api\InvoiceProcessInterface;
use Aune\AutoInvoice\Api\InvoiceProcessInterfaceFactory;
use Aune\AutoInvoice\Console\ProcessCommand;

/**
 * @SuppressWarnings(PHPMD.ShortVariable)
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\Aune\AutoInvoice\Console\ProcessCommand::class)]
class ProcessCommandTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var State|\PHPUnit\Framework\MockObject\MockObject
     */
    private $stateMock;
    
    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;
    
    /**
     * @var InvoiceProcessInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $invoiceProcessMock;

    /**
     * @var InvoiceProcessInterfaceFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $invoiceProcessFactoryMock;
    
    /**
     * @var ProcessCommand
     */
    private $processCommand;
    
    protected function setUp(): void
    {
        $this->stateMock = $this->getMockBuilder(State::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->invoiceProcessMock = $this->createMock(InvoiceProcessInterface::class);
        $this->invoiceProcessFactoryMock = $this->getMockBuilder(InvoiceProcessInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->invoiceProcessFactoryMock->method('create')->willReturn($this->invoiceProcessMock);
        
        $this->processCommand = new ProcessCommand(
            $this->stateMock,
            $this->loggerMock,
            $this->invoiceProcessFactoryMock
        );
    }
    
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
        
        $itemMocks = [];
        for ($i=0; $i<10; $i++) {
            $orderMock = $this->getMockBuilder(Order::class)
                ->disableOriginalConstructor()
                ->getMock();
            
            $itemMock = $this->createMock(InvoiceProcessItemInterface::class);
            $itemMock->expects(self::any())
                ->method('getOrder')
                ->willReturn($orderMock);
            
            $itemMocks []= $itemMock;
        }
        
        $this->invoiceProcessMock->expects(self::once())
            ->method('getItemsToProcess')
            ->willReturn($itemMocks);
        
        $this->invoiceProcessMock->expects(self::exactly(0))
            ->method('invoice');
        
        $this->processCommand->run($inputMock, $outputMock);
    }
    
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
        
        $itemMocks = [];
        for ($i=0; $i<10; $i++) {
            $orderMock = $this->getMockBuilder(Order::class)
                ->disableOriginalConstructor()
                ->getMock();
            
            $itemMock = $this->createMock(InvoiceProcessItemInterface::class);
            $itemMock->expects(self::any())
                ->method('getOrder')
                ->willReturn($orderMock);
            
            $itemMocks []= $itemMock;
        }
        
        $this->invoiceProcessMock->expects(self::once())
            ->method('getItemsToProcess')
            ->willReturn($itemMocks);
        
        $this->invoiceProcessMock->expects(self::exactly(count($itemMocks)))
            ->method('invoice');
        
        $this->processCommand->run($inputMock, $outputMock);
    }

    /**
     * Uses real console input (the mocked tests above cannot catch how Symfony parses the option).
     *
     * @dataProvider getDryRunInputDataProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getDryRunInputDataProvider')]
    public function testDryRunWithRealInput(array $arguments, bool $expectInvoices)
    {
        $orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $itemMock = $this->createMock(InvoiceProcessItemInterface::class);
        $itemMock->method('getOrder')->willReturn($orderMock);

        $this->invoiceProcessMock->method('getItemsToProcess')->willReturn([$itemMock]);
        $this->invoiceProcessMock->expects($expectInvoices ? self::once() : self::never())
            ->method('invoice');

        $output = new \Symfony\Component\Console\Output\BufferedOutput();
        $this->processCommand->run(new \Symfony\Component\Console\Input\ArrayInput($arguments), $output);

        self::assertSame(
            !$expectInvoices,
            str_contains($output->fetch(), 'This is a dry run')
        );
    }

    /**
     * @return array
     */
    public static function getDryRunInputDataProvider()
    {
        return [
            'no option' => [[], true],
            'bare --dry-run' => [['--dry-run' => null], false],
            '--dry-run=1' => [['--dry-run' => '1'], false],
            '--dry-run=0' => [['--dry-run' => '0'], true],
        ];
    }
}
