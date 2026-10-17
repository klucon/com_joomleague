<?php
declare(strict_types=1);
namespace Joomla\CMS\Language {
    final class Text { public static function _(string $key): string { return $key; } }
}
namespace {
    define('_JEXEC', 1);
    require dirname(__DIR__, 2).'/administrator/components/com_joomleague/src/Service/MatchScheduleEditor.php';
    foreach ([''=>'', '07:00'=>'07:00', '07:00:00'=>'07:00', ' 23:59:00 '=>'23:59'] as $input=>$expected) {
        if (\Joomleague\Component\Joomleague\Administrator\Service\MatchScheduleEditor::normalizeTime($input)!==$expected) throw new \RuntimeException('Schedule time normalization failed.');
    }
    foreach (['24:00', '12:60', '7:00', '07:00:01', '07:00:00:00', 'tomorrow', '07:00<script>'] as $input) {
        try { \Joomleague\Component\Joomleague\Administrator\Service\MatchScheduleEditor::normalizeTime($input); }
        catch (\UnexpectedValueException) { continue; }
        throw new \RuntimeException('Invalid schedule time accepted.');
    }
    echo "PASS: minute schedule accepts native zero seconds and rejects invalid/nonzero-second input\n";
}
