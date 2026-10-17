<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Site\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Uri\Uri;
use Joomleague\Component\Joomleague\Domain\Service\MediaPathResolver;

final class MediaResolver
{
	public static function image(string $kind, ?string ...$paths): string
	{
		$params = ComponentHelper::getParams('com_joomleague');
		try {
			$base = MediaPathResolver::basePath((string) $params->get('images_base_path', MediaPathResolver::DEFAULT_BASE));
		} catch (\InvalidArgumentException) {
			$base = MediaPathResolver::DEFAULT_BASE;
		}
		$resolver = new MediaPathResolver(JPATH_ROOT, Uri::root(true), $base);
		$image = $resolver->resolve($paths);
		if ($image !== '') return $image;
		$field = match ($kind) {
			'club' => 'placeholder_club_logo',
			'team' => 'placeholder_team_picture',
			'person' => 'placeholder_person_picture',
			'venue' => 'placeholder_venue_picture',
			default => '',
		};
		return $field === '' ? '' : $resolver->resolve([(string) $params->get($field, '')], true);
	}

	public static function participantImage(array|object $participant): string
	{
		$item = (array) $participant;
		return match ((string) ($item['entry_kind'] ?? '')) {
			'team' => self::image('team', $item['team_logo'] ?? null, $item['club_logo'] ?? null, $item['team_picture'] ?? null),
			'person' => self::image('person', $item['person_picture'] ?? null),
			default => self::image('', $item['image'] ?? null),
		};
	}

	public static function participant(object $item): void
	{
		$image = self::participantImage($item);
		$item->team_logo = ($item->entry_kind ?? '') === 'team' ? $image : '';
		$item->team_picture = self::image('', $item->team_picture ?? null);
		$item->person_picture = ($item->entry_kind ?? '') === 'person' ? $image : '';
	}
}
