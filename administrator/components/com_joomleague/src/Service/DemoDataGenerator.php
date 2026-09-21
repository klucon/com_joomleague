<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;
use RuntimeException;

final class DemoDataGenerator
{
	public const PROFILES = [
		'football' => 'COM_JOOMLEAGUE_DEMODATA_PROFILE_FOOTBALL',
		'basketball' => 'COM_JOOMLEAGUE_DEMODATA_PROFILE_BASKETBALL',
		'ice_hockey' => 'COM_JOOMLEAGUE_DEMODATA_PROFILE_ICE_HOCKEY',
		'volleyball' => 'COM_JOOMLEAGUE_DEMODATA_PROFILE_VOLLEYBALL',
		'futsal' => 'COM_JOOMLEAGUE_DEMODATA_PROFILE_FUTSAL',
		'floorball' => 'COM_JOOMLEAGUE_DEMODATA_PROFILE_FLOORBALL',
		'rugby' => 'COM_JOOMLEAGUE_DEMODATA_PROFILE_RUGBY',
		'esports' => 'COM_JOOMLEAGUE_DEMODATA_PROFILE_ESPORTS',
		'bowling' => 'COM_JOOMLEAGUE_DEMODATA_PROFILE_BOWLING',
		'chess' => 'COM_JOOMLEAGUE_DEMODATA_PROFILE_CHESS',
		'darts' => 'COM_JOOMLEAGUE_DEMODATA_PROFILE_DARTS',
		'tennis' => 'COM_JOOMLEAGUE_DEMODATA_PROFILE_TENNIS',
		'mma_boxing' => 'COM_JOOMLEAGUE_DEMODATA_PROFILE_MMA_BOXING',
		'motorsport' => 'COM_JOOMLEAGUE_DEMODATA_PROFILE_MOTORSPORT',
		'running_race' => 'COM_JOOMLEAGUE_DEMODATA_PROFILE_RUNNING_RACE',
	];

	public function __construct(private readonly DatabaseInterface $database)
	{
	}

	/** @param list<string> $profileCodes
	 *  @return array<string, array<string, mixed>>
	 */
	public function generate(array $profileCodes, int $actorId): array
	{
		if ((new RuntimeDataResetter($this->database))->hasRuntimeData()) {
			throw new RuntimeException('COM_JOOMLEAGUE_DEMODATA_ERROR_NOT_EMPTY');
		}

		$profileCodes = array_values(array_unique(array_filter(
			$profileCodes,
			static fn (string $code): bool => array_key_exists($code, self::PROFILES)
		)));

		if ($profileCodes === []) {
			throw new RuntimeException('COM_JOOMLEAGUE_DEMODATA_ERROR_PROFILE');
		}

		$factory = require JPATH_ADMINISTRATOR . '/components/com_joomleague/resources/demo/populate-demo.php';

		if (!is_callable($factory)) {
			throw new RuntimeException('Demo data factory is invalid.');
		}

		return $factory($this->database, $actorId, $profileCodes);
	}
}
