<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Administrator\Service;

defined('_JEXEC') or die;

final class MatchResultValidationException extends \InvalidArgumentException
{
	public function __construct(private readonly string $languageKey)
	{
		parent::__construct($languageKey);
	}

	public function getLanguageKey(): string
	{
		return $this->languageKey;
	}
}
