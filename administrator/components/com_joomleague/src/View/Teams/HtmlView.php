<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1); namespace Joomleague\Component\Joomleague\Administrator\View\Teams; \defined('_JEXEC') or die; use Joomleague\Component\Joomleague\Administrator\View\Common\AdminListView; final class HtmlView extends AdminListView {protected function configure():array{return ['title'=>'COM_JOOMLEAGUE_TEAMS_TITLE','caption'=>'COM_JOOMLEAGUE_TEAMS_TITLE','icon'=>'users','singular'=>'team','plural'=>'teams','primary'=>'name','state'=>false,'toolbar_links'=>[['url'=>'index.php?option=com_fields&context=com_joomleague.team','label'=>'COM_JOOMLEAGUE_CUSTOM_FIELDS','icon'=>'icon-list']],'columns'=>[['field'=>'name','label'=>'COM_JOOMLEAGUE_FIELD_NAME','sort'=>'a.name'],['field'=>'club','label'=>'COM_JOOMLEAGUE_TEAM_FIELD_CLUB'],['field'=>'short_name','label'=>'COM_JOOMLEAGUE_TEAM_FIELD_SHORT_NAME'],['field'=>'picture','label'=>'COM_JOOMLEAGUE_FIELD_IMAGE','type'=>'image','image_placeholder'=>'team_picture'],['field'=>'id','label'=>'JGRID_HEADING_ID','sort'=>'a.id']]];}}
