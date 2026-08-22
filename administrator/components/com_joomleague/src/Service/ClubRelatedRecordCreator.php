<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Filter\OutputFilter;
use Joomla\Database\DatabaseInterface;
use Joomleague\Component\Joomleague\Domain\Service\UuidFactory;

final class ClubRelatedRecordCreator
{
	public function __construct(private readonly DatabaseInterface $database)
	{
	}

	/** @param array<string,mixed> $club */
	public function create(int $clubId, array $club, bool $createTeam, bool $createVenue, int $actorId): void
	{
		if ($clubId < 1 || $actorId < 0) throw new \InvalidArgumentException('Club and actor identifiers are invalid.');
		$name = trim((string) ($club['name'] ?? ''));
		if ($name === '') throw new \InvalidArgumentException('Club name is required for related records.');
		$shortName = trim((string) ($club['short_name'] ?? ''));
		$alias = OutputFilter::stringURLSafe($name);
		$now = gmdate('Y-m-d H:i:s');

		if ($createTeam) {
			$row = (object) [
				'uuid' => UuidFactory::v4(), 'club_id' => $clubId, 'name' => $name,
				'middle_name' => $shortName, 'short_name' => mb_substr($shortName, 0, 50),
				'alias' => $alias, 'published' => 1, 'ordering' => 0,
				'created' => $now, 'created_by' => $actorId,
			];
			$this->database->insertObject('#__joomleague_team', $row);
		}

		if ($createVenue) {
			$row = (object) [
				'uuid' => UuidFactory::v4(), 'owner_club_id' => $clubId, 'name' => $name,
				'alias' => $alias, 'short_name' => $shortName, 'published' => 1, 'ordering' => 0,
				'created' => $now, 'created_by' => $actorId,
			];
			$this->database->insertObject('#__joomleague_venue', $row);
		}
	}
}
