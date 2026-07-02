<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Table;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;

final class TemplateTable extends Table
{
	protected $_supportNullValue = true;

	public function __construct(DatabaseInterface $database, ?DispatcherInterface $dispatcher = null)
	{
		parent::__construct('#__joomleague_template_config', 'id', $database, $dispatcher);
	}

	public function check(): bool
	{
		if (!parent::check()) {
			return false;
		}

		$this->project_id = (int) $this->project_id;
		$this->template = trim((string) $this->template);
		$this->func = trim((string) $this->func);
		$this->title = trim((string) $this->title);
		$this->params = trim((string) $this->params);
		$this->published = (int) $this->published;

		if ($this->project_id < 1 || $this->template === '' || $this->title === '') {
			$this->setError(Text::_('COM_JOOMLEAGUE_TEMPLATE_ERROR_REQUIRED'));

			return false;
		}

		if ($this->params === '') {
			$this->params = '{}';
		}

		if (!json_validate($this->params)) {
			$this->setError(Text::_('COM_JOOMLEAGUE_TEMPLATE_ERROR_PARAMS_INVALID'));

			return false;
		}

		return true;
	}
}
