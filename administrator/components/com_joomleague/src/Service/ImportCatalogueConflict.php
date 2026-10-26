<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Administrator\Service;
defined('_JEXEC') or die;

final class ImportCatalogueConflict extends \RuntimeException
{
	public function __construct(public readonly string $entity, public readonly string $identity)
	{
		parent::__construct('COM_JOOMLEAGUE_DATAIMPORT_ERROR_CATALOGUE_CONFLICT');
	}
}
