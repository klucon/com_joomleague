<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);namespace Joomleague\Component\Joomleague\Administrator\Model;\defined('_JEXEC')or die;
use Joomla\CMS\Application\AdministratorApplication;
use Joomla\Database\ParameterType;
final class RoundModel extends EntityAdminModel
{
 protected string $entityName='round';private AdministratorApplication $application;public function setApplication(AdministratorApplication $a):void{$this->application=$a;}
 public function delete(&$pks):bool{$ids=array_map('intval',(array)$pks);$assets=[];if($ids){$db=$this->getDatabase();$assets=$db->setQuery('SELECT asset_id FROM #__joomleague_match WHERE round_id IN ('.implode(',',$ids).') AND asset_id IS NOT NULL')->loadColumn();}if(!parent::delete($pks))return false;if($assets)$this->getDatabase()->setQuery('DELETE FROM #__assets WHERE id IN ('.implode(',',array_map('intval',$assets)).')')->execute();return true;}
 protected function loadFormData():object{$item=$this->getItem();if(!$item->project_id){$input=$this->application->getInput();$projectId=$input->getInt('project_id');if(!$projectId){$pid=$input->get('pid',[],'array');$projectId=(int)($pid[0]??0);}$item->project_id=$projectId?:$this->application->getUserState('com_joomleague.rounds.project_id');}return $item;}
 protected function prepareTable($t):void{$t->project_id=(int)$t->project_id;$t->roundcode=(int)$t->roundcode;$t->published=(int)$t->published;$t->name=trim((string)$t->name);$t->round_date_first=trim((string)$t->round_date_first)?:null;$t->round_date_last=trim((string)$t->round_date_last)?:null;parent::prepareTable($t);}

	/**
	 * Přesune kolo na nový termín: round_date_first/last i match_date všech zápasů
	 * kola se posunou o stejný počet dní (čas zápasů zůstává). Vrací project_id.
	 */
	public function moveDate(int $roundId, string $newDate): int
	{
		$newDate = trim($newDate);

		try {
			$target = new \DateTimeImmutable($newDate);
		} catch (\Throwable $exception) {
			throw new \RuntimeException('COM_JOOMLEAGUE_ROUND_MOVE_INVALID_DATE');
		}

		$db = $this->getDatabase();
		$round = $db->setQuery(
			$db->createQuery()
				->select($db->quoteName(['id', 'project_id', 'round_date_first', 'round_date_last']))
				->from($db->quoteName('#__joomleague_round'))
				->where($db->quoteName('id') . ' = :id')
				->bind(':id', $roundId, ParameterType::INTEGER)
		)->loadObject();

		if (!$round) {
			throw new \RuntimeException('COM_JOOMLEAGUE_ROUND_MOVE_NOT_FOUND');
		}

		if ($round->round_date_first) {
			$anchor = new \DateTimeImmutable(substr((string) $round->round_date_first, 0, 10));
			$deltaDays = (int) $anchor->diff($target)->format('%r%a');
			$first = $anchor->modify($deltaDays . ' days')->format('Y-m-d');
			$last = $round->round_date_last
				? (new \DateTimeImmutable(substr((string) $round->round_date_last, 0, 10)))->modify($deltaDays . ' days')->format('Y-m-d')
				: $first;
		} else {
			$deltaDays = 0;
			$first = $target->format('Y-m-d');
			$last = $first;
		}

		$db->transactionStart();

		try {
			$db->setQuery(
				$db->createQuery()
					->update($db->quoteName('#__joomleague_round'))
					->set($db->quoteName('round_date_first') . ' = :first')
					->set($db->quoteName('round_date_last') . ' = :last')
					->where($db->quoteName('id') . ' = :id')
					->bind(':first', $first)
					->bind(':last', $last)
					->bind(':id', $roundId, ParameterType::INTEGER)
			)->execute();

			if ($deltaDays !== 0) {
				$db->setQuery(
					$db->createQuery()
						->update($db->quoteName('#__joomleague_match'))
						->set($db->quoteName('match_date') . ' = DATE_ADD(' . $db->quoteName('match_date') . ', INTERVAL :delta DAY)')
						->where($db->quoteName('round_id') . ' = :rid')
						->where($db->quoteName('match_date') . ' IS NOT NULL')
						->bind(':delta', $deltaDays, ParameterType::INTEGER)
						->bind(':rid', $roundId, ParameterType::INTEGER)
				)->execute();
			}

			$db->transactionCommit();
		} catch (\Throwable $exception) {
			$db->transactionRollback();
			throw $exception;
		}

		return (int) $round->project_id;
	}
}
