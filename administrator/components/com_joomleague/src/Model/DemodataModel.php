<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomleague\Component\Joomleague\Administrator\Service\DemoDataGenerator;
use Joomleague\Component\Joomleague\Administrator\Service\RuntimeDataResetter;
use Joomleague\Component\Joomleague\Administrator\Service\RuntimeResetConfiguration;

final class DemodataModel extends BaseDatabaseModel
{
	/** @return array<string, string> */
	public function getProfiles(): array
	{
		return DemoDataGenerator::PROFILES;
	}

	/** @return array{enabled: bool, has_runtime_data: bool, confirmation: string} */
	public function getStatus(): array
	{
		$configuration = new RuntimeResetConfiguration($this->getDatabase());

		return [
			'enabled' => $configuration->isEnabled(),
			'has_runtime_data' => (new RuntimeDataResetter($this->getDatabase()))->hasRuntimeData(),
			'confirmation' => RuntimeDataResetter::CONFIRMATION,
		];
	}

	public function isResetEnabled(): bool
	{
		return (new RuntimeResetConfiguration($this->getDatabase()))->isEnabled();
	}

	/** @param list<string> $profiles
	 *  @return array<string, array<string, mixed>>
	 */
	public function generate(array $profiles, int $actorId): array
	{
		return (new DemoDataGenerator($this->getDatabase()))->generate($profiles, $actorId);
	}

	public function reset(): int
	{
		$configuration = new RuntimeResetConfiguration($this->getDatabase());

		if (!$configuration->isEnabled()) {
			throw new \RuntimeException('COM_JOOMLEAGUE_DEMODATA_ERROR_DISABLED');
		}

		$count = (new RuntimeDataResetter($this->getDatabase()))->reset();
		$configuration->disable();

		return $count;
	}
}
