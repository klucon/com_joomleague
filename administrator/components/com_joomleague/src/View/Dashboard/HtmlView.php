<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\View\Dashboard;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
	/** @var array<string, int> */
	public array $overview = [];

	/** @var array{id:int,name:string}|null */
	public ?array $siteClub = null;

	/** @var list<array{id:int,round_id:int,project_id:int,project_name:string,scheduled_start:?string,status_code:string,played_without_result:bool,home:string,away:string,our_slot:int}> */
	public array $clubMatches = [];

	public string $clubMatchLinkTarget = 'match';

	public string $clubScheduleDisplay = 'all';

	public function display($tpl = null): void
	{
		$model = $this->getModel();
		$this->overview = $model->getOverview();
		$this->siteClub = $model->getSiteClub();

		if ($this->siteClub !== null) {
			$params = ComponentHelper::getParams('com_joomleague');
			$this->clubMatchLinkTarget = (string) $params->get('dashboard_match_link', 'match');
			$this->clubScheduleDisplay = (string) $params->get('dashboard_schedule_display', 'all');

			if ($this->clubScheduleDisplay !== 'hide') {
				$this->clubMatches = $model->getClubMatches((int) $params->get('dashboard_match_limit', 9));
			}
		}

		ToolbarHelper::title(Text::_('COM_JOOMLEAGUE_DASHBOARD_TITLE'), 'joomleague');
		ToolbarHelper::preferences('com_joomleague');

		parent::display($tpl);
	}
}
