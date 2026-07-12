<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1); namespace Joomleague\Component\Joomleague\Administrator\Model; \defined('_JEXEC') or die; use Joomla\Database\QueryInterface;
final class TeamsModel extends EntityListModel { protected array $searchColumns=['a.name','a.short_name','c.name']; protected function buildQuery():QueryInterface{$d=$this->getDatabase();return $d->createQuery()->select('a.*,'.$d->quoteName('c.name','club').','.$d->quoteName('u.name','editor'))->from($d->quoteName('#__joomleague_team','a'))->join('LEFT',$d->quoteName('#__joomleague_club','c'),$d->quoteName('c.id').'='.$d->quoteName('a.club_id'))->join('LEFT',$d->quoteName('#__users','u'),$d->quoteName('u.id').'='.$d->quoteName('a.checked_out'));}}
