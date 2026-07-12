<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);namespace Joomleague\Component\Joomleague\Administrator\View\Round;\defined('_JEXEC') or die;use Joomleague\Component\Joomleague\Administrator\View\Common\AdminFormView;final class HtmlView extends AdminFormView{protected function configure():array{return ['new'=>'COM_JOOMLEAGUE_ROUND_NEW','edit'=>'COM_JOOMLEAGUE_ROUND_EDIT','icon'=>'calendar','singular'=>'round','details'=>'COM_JOOMLEAGUE_FIELDSET_DETAILS','main'=>['project_id','roundcode','name','alias','round_date_first','round_date_last'],'side'=>[],'publishing'=>['published','ordering']];}}
