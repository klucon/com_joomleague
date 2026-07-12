<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1); namespace Joomleague\Component\Joomleague\Administrator\View\Team; \defined('_JEXEC') or die; use Joomleague\Component\Joomleague\Administrator\View\Common\AdminFormView; final class HtmlView extends AdminFormView {protected function configure():array{return ['new'=>'COM_JOOMLEAGUE_TEAM_NEW','edit'=>'COM_JOOMLEAGUE_TEAM_EDIT','icon'=>'users','singular'=>'team','details'=>'COM_JOOMLEAGUE_FIELDSET_DETAILS','main'=>['name','alias','club_id','short_name','middle_name','website','info','notes'],'side'=>['media'=>'COM_JOOMLEAGUE_FIELDSET_MEDIA'],'publishing'=>['ordering']];}}
