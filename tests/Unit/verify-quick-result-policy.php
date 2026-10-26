<?php
declare(strict_types=1);
define('_JEXEC', 1);
$root = dirname(__DIR__, 2);
require $root . '/administrator/components/com_joomleague/src/Service/CanonicalJson.php';
require $root . '/administrator/components/com_joomleague/src/Service/MatchResultEditorSchemaBuilder.php';
require $root . '/administrator/components/com_joomleague/src/Service/MatchQuickResultEditor.php';
use Joomleague\Component\Joomleague\Administrator\Service\MatchQuickResultEditor;
$count = 0;
foreach (glob($root . '/administrator/components/com_joomleague/resources/sport-profiles/*.json') as $file) {
    $profile = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
    $score = $profile['match']['score'];
    $expected = $score['type'] === 'numeric_score' && $score['aggregation']['mode'] === 'validate'
        && in_array($score['value_type'], ['integer','decimal'], true) && !isset($profile['match']['encounter'])
        && $profile['contest']['type'] === 'head_to_head' && in_array('final', $profile['match']['result_status_codes'], true)
        && in_array('completed', $profile['match']['outcome_codes'], true);
    if (MatchQuickResultEditor::supports($profile) !== $expected) throw new RuntimeException('Quick eligibility mismatch: ' . basename($file));
    $revision = MatchQuickResultEditor::revision(1, 2, $profile, [3,4], '');
    foreach ([[1,2,$profile,[3,4],'5'],[1,2,$profile,[4,3],''],[2,2,$profile,[3,4],''],[1,3,$profile,[3,4],''],[1,2,array_replace($profile,['quick_test_revision'=>1]),[3,4],'']] as $changed) {
        if ($revision === MatchQuickResultEditor::revision(...$changed)) throw new RuntimeException('Revision does not detect changed context');
    }
    ++$count;
}
if ($count !== 17) throw new RuntimeException('Expected all seventeen profiles');
echo "PASS: all 17 profile quick-score policies and result/participant/round/profile revision boundaries.\n";
