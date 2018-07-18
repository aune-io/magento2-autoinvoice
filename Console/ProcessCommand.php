<?php

namespace Aune\AutoInvoice\Console;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Magento\Framework\App\State;
use Aune\AutoInvoice\Api\InvoiceProcessInterface;

class ProcessCommand extends Command
{
	const OPTION_DRY_RUN = 'dry-run';
	
    /**
     * @var State
     */
    private $state;
    
    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * @var InvoiceProcessInterface
     */
    private $invoiceProcess;
    
    /**
     * @param State $state
     * @param LoggerInterface $logger
     * @param InvoiceProcessInterface $invoiceProcess
     */
    public function __construct(
        State $state,
        LoggerInterface $logger,
    	InvoiceProcessInterface $invoiceProcess
    ) {
        $this->state = $state;
        $this->logger = $logger;
        $this->invoiceProcess = $invoiceProcess;
        
        parent::__construct();
    }

    /**
     * Configure command options
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                self::OPTION_DRY_RUN,
                null,
                InputOption::VALUE_OPTIONAL,
                'Simulation mode',
                false
            )
        ];

        $this->setName('aune:autoinvoice:process')
            ->setDescription('Invoice completed orders')
            ->setDefinition($options);

        parent::configure();
    }

    /**
     * Execute the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Exception
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->state->getAreaCode();
        } catch (\Exception $e) {
            $this->state->setAreaCode('adminhtml');
        }
        
        $output->writeln('<fg=green>Starting auto invoice procedure</>');
        $dryRun = $input->getOption(self::OPTION_DRY_RUN);
        if ($dryRun) {
            $output->writeln('<fg=yellow>This is a dry run, no orders will actually be invoiced.</>');
        }
        
        $collection = $this->invoiceProcess->getOrdersToInvoice();
        foreach ($collection as $order) {
            try {
                
                $message = sprintf(
    				'Invoicing completed order #%s',
    				$order->getIncrementId()
    			);
    			$output->writeln('<fg=green>' . $message . '</>');
    			
                if ($dryRun) {
                    continue;
                }
                
                $this->logger->info($message);
			    $this->invoiceProcess->invoice($order);
			    
        	} catch (\Exception $ex) {
        		$output->writeln(sprintf(
    				'<fg=red>%s</>',
    				$ex->getMessage()
    			));
    			$this->logger->critical($ex->getMessage());
        	}
        }
        
    	$output->writeln('<fg=green>Auto invoice procedure completed.</>');
    }
}
