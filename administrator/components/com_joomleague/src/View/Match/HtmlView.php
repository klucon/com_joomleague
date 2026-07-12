<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);namespace Joomleague\Component\Joomleague\Administrator\View\Match;\defined('_JEXEC') or die;use Joomleague\Component\Joomleague\Administrator\View\Common\AdminFormView;final class HtmlView extends AdminFormView{protected function configure():array{return ['new'=>'COM_JOOMLEAGUE_MATCH_NEW','edit'=>'COM_JOOMLEAGUE_MATCH_EDIT','icon'=>'list','singular'=>'match','details'=>'COM_JOOMLEAGUE_FIELDSET_DETAILS','main'=>['round_id','match_number','match_date','projectteam1_id','projectteam2_id','playground_id','crowd','team1_result','team2_result','summary','preview'],'side'=>['status'=>'COM_JOOMLEAGUE_MATCH_FIELDSET_STATUS'],'publishing'=>['published']];}}
