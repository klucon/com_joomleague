<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

\defined('_JEXEC') or die;


final class TeamController extends EntityFormController
{
	protected $text_prefix = 'COM_JOOMLEAGUE_TEAM';
	protected $view_list = 'teams';
}
