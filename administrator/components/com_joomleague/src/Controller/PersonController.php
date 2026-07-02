<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Controller;

\defined('_JEXEC') or die;


final class PersonController extends EntityFormController
{
	protected $text_prefix = 'COM_JOOMLEAGUE_PERSON';
	protected $view_list = 'persons';
}
