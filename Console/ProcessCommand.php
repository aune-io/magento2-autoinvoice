<?php

namespace Aune\AutoInvoice\Console;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Magento\Framework\App\State;
use Aune\AutoInvoice\Api\InvoiceProcessInterfaceFactory;

class ProcessCommand extends Command
{
    public const COMMAND_NAME = 'aune:autoinvoice:process';
    public const COMMAND_DESCRIPTION = 'Creates invoices according to configuration.';
    public const OPTION_DRY_RUN = 'dry-run';

    /**
     * @var State
     */
    private $state;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var InvoiceProcessInterfaceFactory
     */
    private $invoiceProcessFactory;

    /**
     * @param State $state
     * @param LoggerInterface $logger
     * @param InvoiceProcessInterfaceFactory $invoiceProcessFactory
     */
    public function __construct(
        State $state,
        LoggerInterface $logger,
        InvoiceProcessInterfaceFactory $invoiceProcessFactory
    ) {
        $this->state = $state;
        $this->logger = $logger;
        $this->invoiceProcessFactory = $invoiceProcessFactory;

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

        $this->setName(self::COMMAND_NAME)
            ->setDescription(self::COMMAND_DESCRIPTION)
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

        $invoiceProcess = $this->invoiceProcessFactory->create();
        $items = $invoiceProcess->getItemsToProcess();
        foreach ($items as $item) {
            try {

                $order = $item->getOrder();
                $message = sprintf(
                    'Invoicing order #%s %s',
                    $order->getIncrementId(),
                    $item->getCaptureMode()
                );
                $output->writeln('<fg=green>' . $message . '</>');

                if ($dryRun) {
                    continue;
                }

                $this->logger->info($message);
                $invoiceProcess->invoice($item);

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
