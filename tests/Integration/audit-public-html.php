<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit(1);
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require JPATH_BASE . '/includes/defines.php';
require JPATH_BASE . '/includes/framework.php';
require JPATH_ADMINISTRATOR . '/components/com_joomleague/src/Service/PublicHtmlSanitizer.php';
$cases = [
    'script' => '<p>Text</p><script>alert(1)</script>',
    'image-handler' => '<img src="x" onerror="alert(1)">',
    'javascript-link' => '<a href="javascript:alert(1)">Link</a>',
    'encoded-link' => '<a href="java&#x73;cript:alert(1)">Link</a>',
    'data-link' => '<a href="data:text/html,<script>alert(1)</script>">Link</a>',
    'svg-handler' => '<svg onload="alert(1)"><a href="javascript:alert(1)">Link</a></svg>',
    'iframe' => '<iframe srcdoc="<script>alert(1)</script>"></iframe>',
    'safe-formatting' => '<p><strong>Text</strong> <a href="https://example.com/">Link</a></p>',
];
$failed = 0;
foreach ($cases as $name => $input) {
    $clean = Joomleague\Component\Joomleague\Domain\Service\PublicHtmlSanitizer::clean($input);
    $dom = new DOMDocument();
    @$dom->loadHTML('<html><body>' . $clean . '</body></html>', LIBXML_NONET);
    $unsafe = false;
    foreach ($dom->getElementsByTagName('*') as $element) {
        if (in_array(strtolower($element->tagName), ['script', 'iframe', 'object', 'embed'], true)) $unsafe = true;
        foreach ($element->attributes as $attribute) {
            if (str_starts_with(strtolower($attribute->name), 'on')) $unsafe = true;
            if (in_array(strtolower($attribute->name), ['href', 'src', 'xlink:href'], true)
                && preg_match('/^(?:javascript|vbscript|data):/i', preg_replace('/\s+/', '', $attribute->value))) $unsafe = true;
        }
    }
    $preserved = $name !== 'safe-formatting' || (str_contains($clean, '<strong>Text</strong>') && str_contains($clean, 'https://example.com/'));
    $ok = !$unsafe && $preserved;
    $failed += $ok ? 0 : 1;
    echo ($ok ? 'PASS' : 'FAIL') . ': public HTML ' . $name . "\n";
}
exit($failed ? 1 : 0);
