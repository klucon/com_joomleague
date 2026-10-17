<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\View\Aboutjoomleague;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomleague\Component\Joomleague\Site\Service\SeoMetadata;

final class HtmlView extends BaseHtmlView
{
	/** @var array{component_version:string,profile_count:int} */
	public array $installation = [];

	public function display($tpl = null): void
	{
		$this->installation = $this->getModel()->getInstallation();
		(new SeoMetadata())->apply(
			$this->getDocument(),
			Text::_('COM_JOOMLEAGUE_ABOUT_VIEW_TITLE'),
			Route::_('index.php?option=com_joomleague&view=aboutjoomleague', false, Route::TLS_IGNORE, true),
			Text::_('COM_JOOMLEAGUE_ABOUT_META_DESCRIPTION')
		);
		parent::display($tpl);
	}
}
