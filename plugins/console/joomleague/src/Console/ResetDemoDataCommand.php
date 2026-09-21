<?php

declare(strict_types=1);

namespace Joomleague\Plugin\Console\Joomleague\Console;

defined('_JEXEC') or die;

use Joomla\Console\Command\AbstractCommand;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Administrator\Service\RuntimeDataResetter;
use Joomleague\Component\Joomleague\Administrator\Service\RuntimeResetConfiguration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ResetDemoDataCommand extends AbstractCommand
{
	protected static $defaultName = 'joomleague:reset-demo-data';

	public function __construct(private readonly DatabaseInterface $database)
	{
		parent::__construct();
	}

	protected function configure(): void
	{
		$this->setDescription('Delete JoomLeague demo/runtime data while preserving bundled sport profiles and component settings.');
		$this->addOption('force', null, InputOption::VALUE_NONE, 'Confirm the destructive reset.');
	}

	protected function doExecute(InputInterface $input, OutputInterface $output): int
	{
		$io = new SymfonyStyle($input, $output);
		$io->title('JoomLeague demo data reset');

		$configuration = new RuntimeResetConfiguration($this->database);

		if (!$configuration->isEnabled()) {
			$io->error('Reset is disabled. Enable Complete data reset in JoomLeague Options first.');

			return Command::FAILURE;
		}

		if (!$input->getOption('force')) {
			$io->error('The --force option is required.');

			return Command::FAILURE;
		}

		try {
			$count = (new RuntimeDataResetter($this->database))->reset();
			$configuration->disable();
		} catch (\Throwable $exception) {
			$io->error($exception->getMessage());

			return Command::FAILURE;
		}

		$io->success(sprintf(
			'%d runtime tables reset. Bundled sport profiles and Joomla/component settings were preserved.',
			$count,
		));

		return Command::SUCCESS;
	}

}
