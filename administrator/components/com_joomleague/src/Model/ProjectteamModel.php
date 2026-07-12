<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);namespace Joomleague\Component\Joomleague\Administrator\Model;\defined('_JEXEC')or die;final class ProjectteamModel extends EntityAdminModel{protected string $entityName='projectteam';protected function prepareTable($t):void{foreach(['project_id','team_id','division_id','standard_playground','start_points','points_finally','neg_points_finally','matches_finally','won_finally','draws_finally','lost_finally','homegoals_finally','guestgoals_finally','diffgoals_finally','is_in_score','use_finally']as$f)$t->$f=(int)$t->$f?:($f==='division_id'||$f==='standard_playground'?null:0);foreach(['info','notes','reason','extended','picture','alias']as$f)$t->$f=trim((string)$t->$f);parent::prepareTable($t);}}
