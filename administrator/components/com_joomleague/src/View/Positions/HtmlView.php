<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1); namespace Joomleague\Component\Joomleague\Administrator\View\Positions; \defined('_JEXEC') or die; use Joomleague\Component\Joomleague\Administrator\View\Common\AdminListView; final class HtmlView extends AdminListView {protected function configure():array{return ['title'=>'COM_JOOMLEAGUE_POSITIONS_TITLE','caption'=>'COM_JOOMLEAGUE_POSITIONS_TITLE','icon'=>'address','singular'=>'position','plural'=>'positions','primary'=>'name','state'=>true,'columns'=>[['field'=>'name','label'=>'COM_JOOMLEAGUE_FIELD_NAME','sort'=>'a.name','type'=>'lang'],['field'=>'sport','label'=>'COM_JOOMLEAGUE_FIELD_SPORT','type'=>'lang'],['field'=>'parent_name','label'=>'COM_JOOMLEAGUE_FIELD_PARENT','type'=>'lang'],['field'=>'persontype','label'=>'COM_JOOMLEAGUE_POSITION_FIELD_PERSONTYPE'],['field'=>'id','label'=>'JGRID_HEADING_ID','sort'=>'a.id']]];}}
