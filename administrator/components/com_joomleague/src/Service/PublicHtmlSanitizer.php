<?php

declare(strict_types=1);

namespace Joomleague\Component\Joomleague\Domain\Service;

defined('_JEXEC') or die;

use Joomla\Filter\InputFilter;

/** Sanitizes rich text before it is rendered by public component views. */
final class PublicHtmlSanitizer
{
	public static function clean(mixed $html): string
	{
		$filter = new InputFilter([], [], InputFilter::ONLY_BLOCK_DEFINED_TAGS, InputFilter::ONLY_BLOCK_DEFINED_ATTRIBUTES);

		return $filter->clean((string) $html, 'html');
	}
}
