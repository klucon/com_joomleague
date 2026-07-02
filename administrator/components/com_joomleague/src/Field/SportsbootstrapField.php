<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

final class SportsbootstrapField extends FormField
{
	protected $type = 'Sportsbootstrap';

	protected function getInput(): string
	{
		$url = Route::_('index.php?option=com_joomleague&task=sportsbootstrap.create&' . Session::getFormToken() . '=1', false);

		return '<a class="btn btn-primary" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">'
			. Text::_('COM_JOOMLEAGUE_CONFIG_SPORTS_BOOTSTRAP_CREATE_BUTTON')
			. '</a><p class="form-text">'
			. Text::_('COM_JOOMLEAGUE_CONFIG_SPORTS_BOOTSTRAP_CREATE_DESC')
			. '</p>';
	}
}
