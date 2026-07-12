<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);namespace Joomleague\Component\Joomleague\Administrator\View\Projectreferee;\defined('_JEXEC')or die;use Joomleague\Component\Joomleague\Administrator\View\Common\AdminFormView;final class HtmlView extends AdminFormView{protected function configure():array{return ['new'=>'COM_JOOMLEAGUE_PROJECTREFEREE_NEW','edit'=>'COM_JOOMLEAGUE_PROJECTREFEREE_EDIT','icon'=>'users','singular'=>'projectreferee','details'=>'COM_JOOMLEAGUE_FIELDSET_DETAILS','main'=>['project_id','person_id','project_position_id','notes','extended'],'side'=>['media'=>'COM_JOOMLEAGUE_FIELDSET_MEDIA'],'publishing'=>['published','ordering']];}}
