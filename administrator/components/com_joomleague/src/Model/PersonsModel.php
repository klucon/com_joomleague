<?php
/**
 * @package     JoomLeague
 * @copyright   Copyright (C) 2026 Ondřej Klučka (https://klucon.cz). All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1); namespace Joomleague\Component\Joomleague\Administrator\Model; \defined('_JEXEC') or die; use Joomla\Database\QueryInterface;
final class PersonsModel extends EntityListModel { protected array $searchColumns=['a.firstname','a.lastname','a.nickname']; protected function buildQuery():QueryInterface{$d=$this->getDatabase();return $d->createQuery()->select('a.*, CONCAT_WS(" ",a.firstname,a.lastname) AS fullname,'.$d->quoteName('p.name','position').','.$d->quoteName('u.name','editor'))->from($d->quoteName('#__joomleague_person','a'))->join('LEFT',$d->quoteName('#__joomleague_position','p'),$d->quoteName('p.id').'='.$d->quoteName('a.position_id'))->join('LEFT',$d->quoteName('#__users','u'),$d->quoteName('u.id').'='.$d->quoteName('a.checked_out'));}}
