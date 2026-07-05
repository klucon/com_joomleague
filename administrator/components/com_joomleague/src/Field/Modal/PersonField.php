<?php

/**
 * Modální výběr osoby pro menu položky JoomLeague.
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
 * Modální picker osoby (dropdown náhrada — vybere se ze seznamu s hledáním).
 */
class PersonField extends ModalSelectField
{
	protected $type = 'Modal_Person';

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
			'view'                  => 'personpicker',
			'layout'                => 'modal',
			'tmpl'                  => 'component',
			Session::getFormToken() => 1,
		]);

		$this->urls['select']        = (string) $link;
		$this->modalTitles['select'] = Text::_('COM_JOOMLEAGUE_SELECT_PERSON');
		$this->hint                  = $this->hint ?: Text::_('COM_JOOMLEAGUE_SELECT_PERSON');

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
				->select($db->quoteName(['firstname', 'lastname']))
				->from($db->quoteName('#__joomleague_person'))
				->where($db->quoteName('id') . ' = :v')
				->bind(':v', $value, ParameterType::INTEGER);
			$row = $db->setQuery($query)->loadObject();

			if ($row) {
				return trim(($row->firstname ?? '') . ' ' . ($row->lastname ?? '')) ?: (string) $value;
			}
		} catch (\Throwable $e) {
			// tiše ignorovat, vrátí se ID
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
