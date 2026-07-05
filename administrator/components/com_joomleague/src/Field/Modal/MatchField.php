<?php

/**
 * Modální výběr zápasu pro menu položky JoomLeague.
 *
 * @author   Ondřej Klučka
 * @package  Klucon.Joomleague
 * @license  GNU General Public License version 2 or later
 */

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Field\Modal;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ModalSelectField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\ParameterType;

/**
 * Modální picker zápasu (dropdown náhrada — vybere se ze seznamu s hledáním).
 */
class MatchField extends ModalSelectField
{
	protected $type = 'Modal_Match';

	public function setup(\SimpleXMLElement $element, $value, $group = null)
	{
		$result = parent::setup($element, $value, $group);

		if (!$result) {
			return $result;
		}

		Factory::getApplication()->getLanguage()->load('com_joomleague', JPATH_ADMINISTRATOR);

		$link = (new Uri())->setPath(Uri::base(true) . '/index.php');
		$link->setQuery([
			'option'                => 'com_joomleague',
			'view'                  => 'matchpicker',
			'layout'                => 'modal',
			'tmpl'                  => 'component',
			Session::getFormToken() => 1,
		]);

		$this->urls['select']        = (string) $link;
		$this->modalTitles['select'] = Text::_('COM_JOOMLEAGUE_SELECT_MATCH');
		$this->hint                  = $this->hint ?: Text::_('COM_JOOMLEAGUE_SELECT_MATCH');

		return $result;
	}

	protected function getValueTitle()
	{
		$value = (int) $this->value ?: 0;

		if (!$value) {
			return '';
		}

		try {
			$db    = $this->getDatabase();
			$query = $db->createQuery()
				->select([
					$db->quoteName('th.name', 'home'),
					$db->quoteName('tg.name', 'away'),
					$db->quoteName('m.match_date'),
				])
				->from($db->quoteName('#__joomleague_match', 'm'))
				->join('LEFT', $db->quoteName('#__joomleague_project_team', 'ph') . ' ON ' . $db->quoteName('ph.id') . ' = ' . $db->quoteName('m.projectteam1_id'))
				->join('LEFT', $db->quoteName('#__joomleague_team', 'th') . ' ON ' . $db->quoteName('th.id') . ' = ' . $db->quoteName('ph.team_id'))
				->join('LEFT', $db->quoteName('#__joomleague_project_team', 'pg') . ' ON ' . $db->quoteName('pg.id') . ' = ' . $db->quoteName('m.projectteam2_id'))
				->join('LEFT', $db->quoteName('#__joomleague_team', 'tg') . ' ON ' . $db->quoteName('tg.id') . ' = ' . $db->quoteName('pg.team_id'))
				->where($db->quoteName('m.id') . ' = :v')
				->bind(':v', $value, ParameterType::INTEGER);
			$row = $db->setQuery($query)->loadObject();

			if ($row) {
				$label = trim(($row->home ?? '') . ' – ' . ($row->away ?? ''), ' –');
				if (!empty($row->match_date) && strpos((string) $row->match_date, '0000-00-00') !== 0) {
					$label .= ' (' . date('d.m.Y', strtotime((string) $row->match_date)) . ')';
				}
				return $label ?: (string) $value;
			}
		} catch (\Throwable $e) {
			// tiše ignorovat
		}

		return (string) $value;
	}

	protected function getRenderer($layoutId = 'default')
	{
		$layout = parent::getRenderer($layoutId);
		$layout->setComponent('com_joomleague');
		$layout->setClient(1);

		return $layout;
	}
}
