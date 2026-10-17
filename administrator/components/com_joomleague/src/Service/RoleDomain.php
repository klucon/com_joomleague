<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

final class RoleDomain
{
	public const PARTICIPANT = 'participant';
	public const TEAM_STAFF = 'team_staff';
	public const ADJUDICATOR = 'adjudicator';
	public const COMPETITION_OFFICIAL = 'competition_official';
	public const EVENT_STAFF = 'event_staff';

	public const VALUES = [
		self::PARTICIPANT,
		self::TEAM_STAFF,
		self::ADJUDICATOR,
		self::COMPETITION_OFFICIAL,
		self::EVENT_STAFF,
	];

	public static function isEntryMember(string $domain): bool
	{
		return in_array($domain, [self::PARTICIPANT, self::TEAM_STAFF], true);
	}

	public static function isCompetitionActor(string $domain): bool
	{
		return in_array($domain, [self::ADJUDICATOR, self::COMPETITION_OFFICIAL, self::EVENT_STAFF], true);
	}
}
