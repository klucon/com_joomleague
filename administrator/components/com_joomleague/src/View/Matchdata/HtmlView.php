<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);namespace Joomleague\Component\Joomleague\Administrator\View\Matchdata;\defined('_JEXEC') or die;use Joomla\CMS\MVC\View\HtmlView as Base;use Joomla\CMS\Toolbar\ToolbarHelper;final class HtmlView extends Base{public object $match;public string $section;public array $rows;public array $types=[];public array $players=[];public array $referees=[];public array $positions=[];public function display($tpl=null):void{$m=$this->getModel();$id=(int)$m->getState('match_id');$this->section=(string)$m->getState('section','events');$this->match=$m->getContext($id);$this->players=$m->getPlayers($id);if($this->section==='events'){$this->rows=$m->getEvents($id);$this->types=$m->getEventTypes($this->match->project_id);}elseif($this->section==='players'){$this->rows=$m->getMatchPlayers($id);$this->positions=$m->getPlayerPositions($this->match->project_id);}elseif($this->section==='statistics'){$this->rows=$m->getStatistics($id);$this->types=$m->getStatisticsTypes($this->match->project_id);}else{$this->section='referees';$this->rows=$m->getReferees($id);$this->referees=$m->getProjectReferees($this->match->project_id);$this->positions=$m->getRefereePositions($this->match->project_id);}ToolbarHelper::title($this->match->home.' – '.$this->match->away,'list');parent::display($tpl);}}
