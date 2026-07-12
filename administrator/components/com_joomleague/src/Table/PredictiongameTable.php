<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Table;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;

final class PredictiongameTable extends Table
{
	protected $_supportNullValue = true;

	public function __construct(DatabaseInterface $database, ?DispatcherInterface $dispatcher = null)
	{
		parent::__construct('#__joomleague_prediction_game', 'id', $database, $dispatcher);
	}

	public function check(): bool
	{
		if (!parent::check()) {
			return false;
		}

		$this->project_id = (int) $this->project_id ?: null;
		$this->name = trim((string) $this->name);
		$this->deadline_minutes = max(0, (int) $this->deadline_minutes);
		$this->points_exact = max(0, (int) $this->points_exact);
		$this->points_tendency = max(0, (int) $this->points_tendency);
		$this->points_goal_diff = max(0, (int) $this->points_goal_diff);
		$this->show_ranking = (int) $this->show_ranking;
		$this->published = (int) $this->published;

		if ($this->project_id === null || $this->name === '') {
			$this->setError(Text::_('COM_JOOMLEAGUE_PREDICTIONGAME_ERROR_REQUIRED'));

			return false;
		}

		return true;
	}
}
