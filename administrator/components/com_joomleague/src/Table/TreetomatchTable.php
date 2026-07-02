<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Table;

\defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;

final class TreetomatchTable extends Table
{
	protected $_supportNullValue = true;

	public function __construct(DatabaseInterface $database, ?DispatcherInterface $dispatcher = null)
	{
		parent::__construct('#__joomleague_treeto_match', 'id', $database, $dispatcher);
	}
}
