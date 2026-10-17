<?php

declare(strict_types=1);

define('_JEXEC', 1);
require dirname(__DIR__, 2) . '/administrator/components/com_joomleague/src/Service/SportCategoryTree.php';

use Joomleague\Component\Joomleague\Administrator\Service\SportCategoryTree;

$tree = new SportCategoryTree();
$rows = [
	['id' => 1, 'parent_id' => 0, 'code' => 'ball-sports'],
	['id' => 2, 'parent_id' => 1, 'code' => 'team-ball-sports'],
	['id' => 3, 'parent_id' => 0, 'code' => 'winter-sports'],
];
$tree->validate($rows);
$tree->validate([]);
$tree->assertRemovable($rows, 2, 0);
$expect = static function (callable $action, string $reason): void {
	try {
		$action();
	} catch (InvalidArgumentException $error) {
		if ($error->getMessage() !== $reason) {
			throw new RuntimeException('Unexpected rejection: ' . $error->getMessage());
		}
		return;
	}
	throw new RuntimeException('Expected rejection: ' . $reason);
};
$expect(fn () => $tree->validate([['id' => 1, 'parent_id' => 1, 'code' => 'self']]), 'category_cycle');
$cycle = $rows;
$cycle[0]['parent_id'] = 2;
$expect(fn () => $tree->validate($cycle), 'category_cycle');
$orphan = $rows;
$orphan[1]['parent_id'] = 99;
$expect(fn () => $tree->validate($orphan), 'category_parent_missing');
$expect(fn () => $tree->validate([...$rows, $rows[0]]), 'category_duplicate');
$duplicate = $rows;
$duplicate[1]['code'] = $duplicate[0]['code'];
$expect(fn () => $tree->validate($duplicate), 'category_duplicate');
$invalid = $rows;
$invalid[0]['id'] = '1';
$expect(fn () => $tree->validate($invalid), 'category_invalid');
$invalid = $rows;
$invalid[0]['code'] = '../invalid';
$expect(fn () => $tree->validate($invalid), 'category_invalid');
$expect(fn () => $tree->assertRemovable($rows, 1, 0), 'category_has_children');
$expect(fn () => $tree->assertRemovable($rows, 2, 1), 'category_in_use');
$expect(fn () => $tree->assertRemovable($rows, 99, 0), 'category_missing');
$deep = [];
for ($id = 1; $id <= 10000; ++$id) {
	$deep[] = ['id' => $id, 'parent_id' => $id - 1, 'code' => 'category-' . $id];
}
$tree->validate(array_reverse($deep));
$expect(fn () => $tree->validate([...$deep, ['id' => 10001, 'parent_id' => 0, 'code' => 'extra']]), 'category_limit');
echo "Sport category tree: valid trees, cycles, parents, uniqueness, deletion and deep hierarchy OK\n";
