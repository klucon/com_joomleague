<?php
declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

use Joomla\CMS\User\User;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/** Bounded person lookup; the club is a convenience filter, not an ACL grant. */
final class EntryMemberPersonOptions
{
	public function __construct(private readonly DatabaseInterface $db) {}

	public function context(int $entryId, int $memberId, User $user): object
	{
		$personId = 0;
		if ($memberId > 0) {
			$query = $this->db->getQuery(true)->select(['entry_id', 'person_id'])->from($this->db->quoteName('#__joomleague_project_entry_member'))
				->where('id = :member')->bind(':member', $memberId, ParameterType::INTEGER);
			$stored = $this->db->setQuery($query)->loadObject();
			if (!$stored) throw new \RuntimeException('Unavailable membership');
			$entryId = (int) $stored->entry_id;
			$personId = (int) $stored->person_id;
		}
		$entry = (new ProjectEntryContextRepository($this->db))->get($entryId);
		$asset = 'com_joomleague.project.' . (int) $entry->project_id;
		if ($user->guest || !$user->authorise('core.manage', 'com_joomleague')
			|| !$user->authorise($memberId > 0 ? 'core.edit' : 'core.create', $asset)
			|| ($entry->profile['entry_model']['members_supported'] ?? false) !== true) throw new \RuntimeException('Lookup not permitted');
		$entry->current_person_id = $personId;
		$clubId = 0;
		if ((int) $entry->team_id > 0) {
			$teamId = (int) $entry->team_id;
			$query = $this->db->getQuery(true)->select('club_id')->from($this->db->quoteName('#__joomleague_team'))
				->where('id = :team')->bind(':team', $teamId, ParameterType::INTEGER);
			$clubId = (int) $this->db->setQuery($query)->loadResult();
		}
		$entry->lookup_club_id = $clubId;
		return $entry;
	}

	/** @return list<object{value:int,text:string}> */
	public function search(object $entry, User $user, string $term, bool $all = false, int $selected = 0): array
	{
		$db = $this->db;
		$levels = array_values(array_filter(array_map('intval', $user->getAuthorisedViewLevels()), static fn($id) => $id > 0));
		if (!$levels) return [];
		$query = $db->getQuery(true)->select(['p.id AS value', 'p.first_name', 'p.last_name'])
			->from($db->quoteName('#__joomleague_person', 'p'))->where('p.access IN (' . implode(',', $levels) . ')');
		$current = (int) $entry->current_person_id;
		if ($selected > 0) {
			$query->where('p.id = :selected')->bind(':selected', $selected, ParameterType::INTEGER);
		}
		if ($selected > 0 && $selected === $current) {
			// Keep the existing historical person selectable, even if now unpublished.
		} else {
			$query->where('p.published = 1');
			$clubId = (int) $entry->lookup_club_id;
			if (!$all && $clubId > 0) {
				$query->where('(p.club_id = :club OR EXISTS (SELECT 1 FROM ' . $db->quoteName('#__joomleague_project_entry_member', 'm')
					. ' INNER JOIN ' . $db->quoteName('#__joomleague_project_entry', 'e') . ' ON e.id = m.entry_id'
					. ' INNER JOIN ' . $db->quoteName('#__joomleague_team', 't') . ' ON t.id = e.team_id'
					. ' WHERE m.person_id = p.id AND t.club_id = :memberClub AND m.published = 1 AND e.published = 1 AND t.published = 1))')
					->bind(':club', $clubId, ParameterType::INTEGER)->bind(':memberClub', $clubId, ParameterType::INTEGER);
			}
		}
		$term = mb_substr(trim($term), 0, 100);
		if ($term !== '') {
			$search = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($term, 'UTF-8')) . '%';
			$query->where("(LOWER(p.first_name) LIKE :first ESCAPE '!' OR LOWER(p.last_name) LIKE :last ESCAPE '!')")
				->bind(':first', $search)->bind(':last', $search);
		}
		$rows = $db->setQuery($query->order('p.last_name ASC, p.first_name ASC, p.id ASC'), 0, 30)->loadObjectList();
		return array_map(static fn($row) => (object) ['value' => (int) $row->value, 'text' => trim($row->last_name . ', ' . $row->first_name, ', ')], $rows);
	}
}
