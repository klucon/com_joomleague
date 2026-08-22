<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Planned;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomleague\Component\Joomleague\Administrator\Service\AdminDomainCatalog;

abstract class AbstractPlannedHtmlView extends BaseHtmlView
{
	protected string $domainCode;

	/** @var array{title: string, description: string, phase: string, icon: string, status: string} */
	public array $domain = [];

	public function display($tpl = null): void
	{
		$this->domain = AdminDomainCatalog::get($this->domainCode);
		ToolbarHelper::title(Text::_($this->domain['title']), $this->domain['icon']);
		$this->addTemplatePath(JPATH_COMPONENT_ADMINISTRATOR . '/tmpl/planned');
		parent::display($tpl);
	}
}
