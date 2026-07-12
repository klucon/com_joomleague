<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

\defined('_JEXEC') or die;

use Joomla\CMS\Filter\OutputFilter;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

final class ClubProvisioningService
{
	public function __construct(private readonly DatabaseInterface $database)
	{
	}

	public function createTeam(int $clubId, string $clubName, string $modified, ?int $modifiedBy): int
	{
		$shortName = $this->abbreviate($clubName, 15);
		$team = (object) [
			'club_id' => $clubId,
			'name' => $clubName,
			'short_name' => $shortName,
			'middle_name' => mb_substr($clubName, 0, 25),
			'alias' => OutputFilter::stringURLSafe($clubName),
			'website' => '',
			'info' => '',
			'notes' => '',
			'picture' => '',
			'extended' => null,
			'ordering' => $this->nextOrdering('#__joomleague_team'),
			'modified' => $modified,
			'modified_by' => $modifiedBy,
		];

		$this->database->insertObject('#__joomleague_team', $team, 'id');

		return (int) $team->id;
	}

	public function createStadium(
		int $clubId,
		string $stadiumName,
		array $clubData,
		string $modified,
		?int $modifiedBy
	): int {
		$stadium = (object) [
			'name' => $stadiumName,
			'short_name' => $this->abbreviate($stadiumName, 15),
			'alias' => OutputFilter::stringURLSafe($stadiumName),
			'address' => trim((string) ($clubData['address'] ?? '')),
			'zipcode' => trim((string) ($clubData['zipcode'] ?? '')),
			'city' => trim((string) ($clubData['location'] ?? '')),
			'country' => trim((string) ($clubData['country'] ?? '')) ?: null,
			'max_visitors' => null,
			'website' => trim((string) ($clubData['website'] ?? '')),
			'picture' => '',
			'notes' => '',
			'club_id' => $clubId,
			'extended' => null,
			'ordering' => $this->nextOrdering('#__joomleague_playground'),
			'modified' => $modified,
			'modified_by' => $modifiedBy,
		];

		$this->database->insertObject('#__joomleague_playground', $stadium, 'id');
		$stadiumId = (int) $stadium->id;
		$query = $this->database->createQuery()
			->update($this->database->quoteName('#__joomleague_club'))
			->set($this->database->quoteName('standard_playground') . ' = :stadiumId')
			->where($this->database->quoteName('id') . ' = :clubId')
			->bind(':stadiumId', $stadiumId)
			->bind(':clubId', $clubId);
		$this->database->setQuery($query)->execute();

		return $stadiumId;
	}

	public function assignFirstClubStadiumAsDefault(int $clubId): void
	{
		$query = $this->database->createQuery()
			->select($this->database->quoteName('id'))
			->from($this->database->quoteName('#__joomleague_playground'))
			->where($this->database->quoteName('club_id') . ' = :club_id')
			->order($this->database->quoteName('ordering') . ' ASC, ' . $this->database->quoteName('id') . ' ASC')
			->bind(':club_id', $clubId, ParameterType::INTEGER);

		$stadiumId = (int) $this->database->setQuery($query, 0, 1)->loadResult();

		if ($stadiumId < 1) {
			return;
		}

		$query = $this->database->createQuery()
			->update($this->database->quoteName('#__joomleague_club'))
			->set($this->database->quoteName('standard_playground') . ' = :stadium_id')
			->where($this->database->quoteName('id') . ' = :club_id')
			->where('(' . $this->database->quoteName('standard_playground') . ' IS NULL OR ' . $this->database->quoteName('standard_playground') . ' = 0)')
			->bind(':stadium_id', $stadiumId, ParameterType::INTEGER)
			->bind(':club_id', $clubId, ParameterType::INTEGER);

		$this->database->setQuery($query)->execute();
	}

	private function nextOrdering(string $table): int
	{
		$query = $this->database->createQuery()->select('COALESCE(MAX(' . $this->database->quoteName('ordering') . '), 0) + 1')
			->from($this->database->quoteName($table));
		$this->database->setQuery($query);

		return (int) $this->database->loadResult();
	}

	private function abbreviate(string $name, int $length): string
	{
		$compact = preg_replace('/[^\p{L}\p{N}]+/u', '', $name) ?: $name;

		return mb_strtoupper(mb_substr($compact, 0, $length));
	}
}
