<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Domain\Service;
defined('_JEXEC') or die;

final class EventRankingOptions
{
    public static function normalize(array $input): array
    {
        $stage = $input['stage_id'] ?? 0;
        if (filter_var($stage, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) === false) {
            throw new \InvalidArgumentException('Invalid event ranking stage.');
        }
        $result = ['stage_id' => (int) $stage];
        foreach (['date_from', 'date_until'] as $key) {
            $value = $input[$key] ?? '';
            if (!is_string($value)) throw new \InvalidArgumentException('Invalid event ranking date.');
            if ($value !== '') {
                $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value, new \DateTimeZone('UTC'));
                if (!$date || $date->format('Y-m-d') !== $value) throw new \InvalidArgumentException('Invalid event ranking date.');
            }
            $result[$key] = $value;
        }
        if ($result['date_from'] !== '' && $result['date_until'] !== '' && $result['date_from'] > $result['date_until']) {
            throw new \InvalidArgumentException('Reversed event ranking period.');
        }
        foreach (['participant_kind' => ['any', 'person', 'team', 'participant'],
            'direction' => ['desc', 'asc'], 'ties' => ['ordinal', 'competition', 'dense']] as $key => $allowed) {
            $value = $input[$key] ?? $allowed[0];
            if (!in_array($value, $allowed, true)) throw new \InvalidArgumentException('Invalid event ranking option: ' . $key);
            $result[$key] = $value;
        }
        $result['strict_code'] = ($input['strict_code'] ?? false) === true;
        $result['include_images'] = ($input['include_images'] ?? false) === true;
        return $result;
    }

    public static function rank(array $rows, string $policy): void
    {
        if (!in_array($policy, ['ordinal', 'competition', 'dense'], true)) throw new \InvalidArgumentException('Invalid tie policy.');
        $previous = null;
        $rank = 0;
        foreach ($rows as $index => $row) {
            if ($policy === 'ordinal' || (string) $row->total_value !== $previous) {
                $rank = $policy === 'dense' ? $rank + 1 : $index + 1;
            }
            $row->rank = $rank;
            $previous = (string) $row->total_value;
        }
    }
}
