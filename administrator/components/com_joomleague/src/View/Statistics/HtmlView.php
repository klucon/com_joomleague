<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1); namespace Joomleague\Component\Joomleague\Administrator\View\Statistics; \defined('_JEXEC') or die; use Joomleague\Component\Joomleague\Administrator\View\Common\AdminListView; final class HtmlView extends AdminListView {protected function configure():array{return ['title'=>'COM_JOOMLEAGUE_STATISTICS_TITLE','caption'=>'COM_JOOMLEAGUE_STATISTICS_TITLE','icon'=>'chart','singular'=>'statistic','plural'=>'statistics','primary'=>'name','state'=>true,'columns'=>[['field'=>'name','label'=>'COM_JOOMLEAGUE_FIELD_NAME','sort'=>'a.name'],['field'=>'short','label'=>'COM_JOOMLEAGUE_STATISTIC_FIELD_SHORT'],['field'=>'sport','label'=>'COM_JOOMLEAGUE_FIELD_SPORT'],['field'=>'class','label'=>'COM_JOOMLEAGUE_STATISTIC_FIELD_HANDLER'],['field'=>'id','label'=>'JGRID_HEADING_ID','sort'=>'a.id']]];}}
