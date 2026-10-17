<?php
declare(strict_types=1);
define('_JEXEC', 1);
require dirname(__DIR__, 2) . '/administrator/components/com_joomleague/src/Service/SqlInsertDataParser.php';
$parser = new Joomleague\Component\Joomleague\Administrator\Service\SqlInsertDataParser();
$sql = "INSERT INTO `#__joomleague_club` (`id`,`name`,`picture`) VALUES (1,'It''s \\'safe\\'',NULL),(2,'SELECT password FROM users',{{profile_version:football}})";
$data = $parser->parse($sql);
if ($data['rows'][0] !== ['1', "It's 'safe'", null] || $data['rows'][1][2] !== ['profile' => 'football']) throw new RuntimeException('Literal decoding failed.');
foreach ([
    'SELECT password FROM users', '(SLEEP(1))', "('x') ON DUPLICATE KEY UPDATE name=(SELECT password FROM users)",
    "('x'); DELETE FROM users", "('x' || (SELECT password FROM users))", "(DEFAULT)", "(0x1234)",
    "('x' /* comment */)", "('unterminated)", "('x'),", "(1,2)", "('x') RETURNING *",
] as $tail) {
    try { $parser->parse('INSERT INTO `#__joomleague_club` (`name`) VALUES ' . $tail); }
    catch (RuntimeException) { continue; }
    throw new RuntimeException('Unsafe or malformed SQL accepted: ' . $tail);
}
echo "PASS: data-only INSERT parser preserves literals and rejects expressions, clauses and malformed input\n";
