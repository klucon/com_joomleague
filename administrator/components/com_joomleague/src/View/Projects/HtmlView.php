<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1); namespace Joomleague\Component\Joomleague\Administrator\View\Projects; \defined('_JEXEC') or die; use Joomleague\Component\Joomleague\Administrator\View\Common\AdminListView; final class HtmlView extends AdminListView {protected function configure():array{return ['title'=>'COM_JOOMLEAGUE_PROJECTS_TITLE','caption'=>'COM_JOOMLEAGUE_PROJECTS_TITLE','icon'=>'grid-2','singular'=>'project','plural'=>'projects','primary'=>'name','state'=>true,'own_template'=>true,'columns'=>[]];}}
