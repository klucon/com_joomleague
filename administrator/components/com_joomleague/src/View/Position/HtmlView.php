<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1); namespace Joomleague\Component\Joomleague\Administrator\View\Position; \defined('_JEXEC') or die; use Joomleague\Component\Joomleague\Administrator\View\Common\AdminFormView; final class HtmlView extends AdminFormView {protected function configure():array{return ['new'=>'COM_JOOMLEAGUE_POSITION_NEW','edit'=>'COM_JOOMLEAGUE_POSITION_EDIT','icon'=>'address','singular'=>'position','details'=>'COM_JOOMLEAGUE_FIELDSET_DETAILS','main'=>['name','alias','sports_type_id','parent_id','persontype'],'side'=>['relations'=>'COM_JOOMLEAGUE_POSITION_FIELDSET_RELATIONS'],'publishing'=>['published','ordering']];}}
