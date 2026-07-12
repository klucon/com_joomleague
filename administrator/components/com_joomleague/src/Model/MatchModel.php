<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Administrator\Model;
\defined('_JEXEC') or die;
use Joomla\CMS\Application\AdministratorApplication;use Joomla\CMS\Date\Date;use Joomla\CMS\Form\Form;use Joomla\CMS\MVC\Model\AdminModel;use Joomla\CMS\Table\Table;use Joomla\Database\ParameterType;
final class MatchModel extends AdminModel
{
 private AdministratorApplication $application;public function setApplication(AdministratorApplication $a):void{$this->application=$a;}
 public function getTable($name='Match',$prefix='Administrator',$options=[]):Table{return parent::getTable($name,$prefix,$options);}
 public function getForm($data=[],$loadData=true):Form|false{return $this->loadForm('com_joomleague.match','match',['control'=>'jform','load_data'=>$loadData]);}
 protected function preprocessForm(Form $form, $data, $group = 'content'): void
 {
  parent::preprocessForm($form,$data,$group);
  $roundId=(int)($data->round_id??0);
  if(!$roundId){$roundId=$this->getRoundIdFromInput();}
  if($roundId<1)return;
  $projectId=$this->getProjectIdByRound($roundId);
  if($projectId<1)return;
  $query='SELECT pt.id, t.name AS name FROM #__joomleague_project_team pt JOIN #__joomleague_team t ON t.id=pt.team_id WHERE pt.project_id='.(int)$projectId.' ORDER BY t.name';
  foreach(['projectteam1_id','projectteam2_id'] as $field){$form->setFieldAttribute($field,'query',$query);}
 }
 protected function loadFormData():object{$i=$this->getItem();if(!$i->round_id)$i->round_id=$this->getRoundIdFromInput()?:$this->application->getUserState('com_joomleague.matches.round_id');if(empty($i->playground_id))$i->playground_id=$this->resolvePlayground((int)($i->projectteam1_id??0))?:($i->playground_id??null);return $i;}

	/**
	 * Stadion domácího týmu: 1) project_team.standard_playground, 2) fallback
	 * club.standard_playground, 3) jinak 0 (nepřiřazeno).
	 */
	public function resolvePlayground(int $projectTeamId): int
	{
		if ($projectTeamId < 1) {
			return 0;
		}

		$db = $this->getDatabase();
		$row = $db->setQuery(
			$db->createQuery()
				->select([$db->quoteName('pt.standard_playground', 'pt_pg'), $db->quoteName('t.club_id')])
				->from($db->quoteName('#__joomleague_project_team', 'pt'))
				->join('LEFT', $db->quoteName('#__joomleague_team', 't') . ' ON t.id = pt.team_id')
				->where('pt.id = :id')
				->bind(':id', $projectTeamId, ParameterType::INTEGER)
		)->loadObject();

		if (!$row) {
			return 0;
		}

		if ((int) $row->pt_pg > 0) {
			return (int) $row->pt_pg;
		}

		if ((int) $row->club_id > 0) {
			$clubPg = (int) $db->setQuery(
				$db->createQuery()->select($db->quoteName('standard_playground'))
					->from($db->quoteName('#__joomleague_club'))
					->where('id = :cid')->bind(':cid', $row->club_id, ParameterType::INTEGER)
			)->loadResult();

			if ($clubPg > 0) {
				return $clubPg;
			}
		}

		return 0;
	}

	/** Název týmu (přes project_team id) pro zobrazení v záhlaví editace zápasu. */
	public function teamLabel(int $projectTeamId): string
	{
		if ($projectTeamId < 1) {
			return '';
		}

		$db = $this->getDatabase();

		return (string) $db->setQuery(
			$db->createQuery()->select($db->quoteName('t.name'))
				->from($db->quoteName('#__joomleague_project_team', 'pt'))
				->join('LEFT', $db->quoteName('#__joomleague_team', 't') . ' ON t.id = pt.team_id')
				->where('pt.id = :id')->bind(':id', $projectTeamId, ParameterType::INTEGER)
		)->loadResult();
	}

	/** Jméno + logo týmu (logo: team.picture → club.logo_small → club.logo_middle). */
	public function teamInfo(int $projectTeamId): object
	{
		$empty = (object) ['name' => '', 'logo' => ''];

		if ($projectTeamId < 1) {
			return $empty;
		}

		$db = $this->getDatabase();
		$row = $db->setQuery(
			$db->createQuery()
				->select([$db->quoteName('t.name'), $db->quoteName('t.picture'), $db->quoteName('c.logo_small'), $db->quoteName('c.logo_middle')])
				->from($db->quoteName('#__joomleague_project_team', 'pt'))
				->join('LEFT', $db->quoteName('#__joomleague_team', 't') . ' ON t.id = pt.team_id')
				->join('LEFT', $db->quoteName('#__joomleague_club', 'c') . ' ON c.id = t.club_id')
				->where('pt.id = :id')->bind(':id', $projectTeamId, ParameterType::INTEGER)
		)->loadObject();

		if (!$row) {
			return $empty;
		}

		$logo = trim((string) $row->picture) ?: (trim((string) $row->logo_small) ?: trim((string) $row->logo_middle));

		return (object) ['name' => (string) $row->name, 'logo' => $logo];
	}
 private function getRoundIdFromInput():int{$input=$this->application->getInput();$id=$input->getInt('round_id');if(!$id){$rid=$input->get('rid',[],'array');$id=(int)($rid[0]??0);}return $id;}
 private function getProjectIdByRound(int $roundId):int{$db=$this->getDatabase();$q=$db->createQuery()->select('project_id')->from('#__joomleague_round')->where('id=:id')->bind(':id',$roundId,ParameterType::INTEGER);return (int)$db->setQuery($q)->loadResult();}
 protected function prepareTable($t):void{foreach(['round_id','projectteam1_id','projectteam2_id','crowd','published','cancel','count_result','show_report','match_result_type'] as $f)$t->$f=(int)$t->$f;$t->playground_id=(int)$t->playground_id?:null;foreach(['team1_result','team2_result','team1_result_ot','team2_result_ot','team1_result_so','team2_result_so'] as $f)$t->$f=$t->$f===''?null:(float)$t->$f;foreach(['summary','preview','cancel_reason','decision_info','extended','match_number','match_result_detail'] as $f)$t->$f=trim((string)$t->$f);$t->match_date=trim((string)$t->match_date)?:null;$t->modified=(new Date())->toSql();$t->modified_by=(int)$this->getCurrentUser()->id?:null;}

	/** Počet částí (poločasy/třetiny) dle sportu projektu daného kola; default 2. */
	public function getPeriodsByRound(int $roundId): int
	{
		if ($roundId < 1) {
			return 2;
		}

		$db = $this->getDatabase();
		$query = $db->createQuery()
			->select('st.periods')
			->from('#__joomleague_round r')
			->join('INNER', '#__joomleague_project p ON p.id = r.project_id')
			->join('INNER', '#__joomleague_sports_type st ON st.id = p.sports_type_id')
			->where('r.id = :rid')
			->bind(':rid', $roundId, ParameterType::INTEGER);

		$periods = (int) $db->setQuery($query)->loadResult();

		return $periods > 0 ? $periods : 2;
	}

	/** Sestaví dílčí skóre po částech (split) z polí formuláře před uložením. */
	public function save($data)
	{
		$jform = $this->application->getInput()->post->get('jform', [], 'raw');

		if (is_array($jform) && (isset($jform['split_home']) || isset($jform['split_away']))) {
			$data['team1_result_split'] = $this->buildSplit($jform['split_home'] ?? []);
			$data['team2_result_split'] = $this->buildSplit($jform['split_away'] ?? []);
		}

		return parent::save($data);
	}

	private function buildSplit($values): string
	{
		if (!is_array($values)) {
			return '';
		}

		ksort($values, SORT_NUMERIC);
		$parts = [];

		foreach ($values as $value) {
			$parts[] = preg_replace('/[^0-9.\-]/', '', (string) $value);
		}

		return trim(implode(';', $parts), '; ') === '' ? '' : implode(';', $parts);
	}

	/**
	 * Inline uložení data a času zápasu (autosave ze seznamu). Vstup z datetime-local
	 * (Y-m-dTH:i) nebo prázdný = vymazat termín. Ukládá naivní lokální čas. Vrací
	 * hodnotu pro datetime-local input (Y-m-dTH:i) nebo prázdno.
	 */
	public function saveDate(int $id, string $value): string
	{
		if ($id < 1) {
			throw new \RuntimeException('COM_JOOMLEAGUE_MATCH_DATE_INVALID');
		}

		$value = trim($value);
		$stored = null;

		if ($value !== '') {
			try {
				$dt = new \DateTimeImmutable(str_replace('T', ' ', $value));
			} catch (\Throwable $exception) {
				throw new \RuntimeException('COM_JOOMLEAGUE_MATCH_DATE_INVALID');
			}

			$stored = $dt->format('Y-m-d H:i:s');
		}

		$db = $this->getDatabase();
		$modified = (new Date())->toSql();
		$modifiedBy = (int) $this->getCurrentUser()->id ?: null;

		$query = $db->createQuery()
			->update($db->quoteName('#__joomleague_match'))
			->set($db->quoteName('modified') . ' = :mod')
			->set($db->quoteName('modified_by') . ' = :mb')
			->where($db->quoteName('id') . ' = :id')
			->bind(':mod', $modified)
			->bind(':mb', $modifiedBy, ParameterType::INTEGER)
			->bind(':id', $id, ParameterType::INTEGER);

		if ($stored === null) {
			$query->set($db->quoteName('match_date') . ' = NULL');
		} else {
			$query->set($db->quoteName('match_date') . ' = :d')->bind(':d', $stored);
		}

		$db->setQuery($query)->execute();

		return $stored === null ? '' : str_replace(' ', 'T', substr($stored, 0, 16));
	}
}
