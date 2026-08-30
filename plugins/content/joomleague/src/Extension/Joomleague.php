<?php

declare(strict_types=1);

namespace Joomleague\Plugin\Content\Joomleague\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Access\Access;
use Joomla\CMS\Event\Content\ContentPrepareEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
use Joomla\Event\SubscriberInterface;

final class Joomleague extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;

	public static function getSubscribedEvents(): array
	{
		return ['onContentPrepare' => 'onContentPrepare'];
	}

	public function onContentPrepare(ContentPrepareEvent $event): void
	{
		$item = $event->getItem();

		if (!is_object($item) || !property_exists($item, 'text') || !is_string($item->text)) {
			return;
		}

		if (!str_contains($item->text, '{joomleague-person') && !str_contains($item->text, '{jl_player}')) {
			return;
		}

		$item->text = preg_replace_callback(
			'/\{joomleague-person\s+(\d+)\}(.*?)\{\/joomleague-person\}/si',
			fn (array $match): string => $this->renderById((int) $match[1], trim(strip_tags($match[2]))),
			$item->text
		) ?? $item->text;
		$item->text = preg_replace_callback(
			'/\{joomleague-person\s+(\d+)\}/i',
			fn (array $match): string => $this->renderById((int) $match[1], ''),
			$item->text
		) ?? $item->text;
		$item->text = preg_replace_callback(
			'/\{jl_player\}(.*?)\{\/jl_player\}/si',
			fn (array $match): string => $this->renderByName(trim(strip_tags($match[1]))),
			$item->text
		) ?? $item->text;
	}

	private function renderById(int $personId, string $label): string
	{
		if ($personId < 1) {
			return $this->escape($label);
		}

		$db = $this->getDatabase();
		$query = $this->baseQuery()
			->where($db->quoteName('id') . ' = :personId')
			->bind(':personId', $personId, ParameterType::INTEGER);
		$person = $db->setQuery($query)->loadObject();

		return $person ? $this->renderLink($person, $label) : $this->escape($label);
	}

	private function renderByName(string $label): string
	{
		if ($label === '') {
			return '';
		}

		$db = $this->getDatabase();
		$query = $this->baseQuery()
			->where('TRIM(CONCAT(' . $db->quoteName('first_name') . ", ' ', " . $db->quoteName('last_name') . ')) = :personName')
			->bind(':personName', $label)
			->order($db->quoteName('id') . ' ASC');
		$person = $db->setQuery($query, 0, 1)->loadObject();

		return $person ? $this->renderLink($person, $label) : $this->escape($label);
	}

	private function baseQuery(): \Joomla\Database\QueryInterface
	{
		$db = $this->getDatabase();
		$levels = array_map('intval', Access::getAuthorisedViewLevels($this->getApplication()->getIdentity()->id));

		return $db->getQuery(true)
			->select([$db->quoteName('id'), $db->quoteName('first_name'), $db->quoteName('last_name')])
			->from($db->quoteName('#__joomleague_person'))
			->where($db->quoteName('published') . ' = 1')
			->where($db->quoteName('access') . ' IN (' . implode(',', $levels ?: [1]) . ')');
	}

	private function renderLink(object $person, string $label): string
	{
		$text = $label !== '' ? $label : trim((string) $person->first_name . ' ' . (string) $person->last_name);
		$url = Route::_('index.php?option=com_joomleague&view=person&person_id=' . (int) $person->id);

		return '<a href="' . $this->escape($url) . '">' . $this->escape($text) . '</a>';
	}

	private function escape(string $value): string
	{
		return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	}
}
