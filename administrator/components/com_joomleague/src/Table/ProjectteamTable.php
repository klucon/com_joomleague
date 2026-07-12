<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);namespace Joomleague\Component\Joomleague\Administrator\Table;\defined('_JEXEC')or die;use Joomla\CMS\Language\Text;use Joomla\CMS\Table\Table;use Joomla\Database\DatabaseInterface;use Joomla\Event\DispatcherInterface;final class ProjectteamTable extends Table{use AssetTableTrait;use MediaFieldTrait;protected $_supportNullValue=true;public function __construct(DatabaseInterface $d,?DispatcherInterface $x=null){parent::__construct('#__joomleague_project_team','id',$d,$x);}public function check():bool{if(!parent::check())return false;if((int)$this->project_id<1||(int)$this->team_id<1){$this->setError(Text::_('COM_JOOMLEAGUE_PROJECTTEAM_ERROR_REQUIRED'));return false;}$this->normalizeMediaField('picture');return true;}}
