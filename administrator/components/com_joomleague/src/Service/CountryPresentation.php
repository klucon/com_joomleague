<?php
declare(strict_types=1);
namespace Joomleague\Component\Joomleague\Domain\Service;
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Event\Event;

final class CountryPresentation
{
    private static array $cache = [];
    private static ?array $catalogue = null;

    public static function resolve(string $value): array
    {
        $language = Factory::getApplication()->getLanguage()->getTag();
        $input = strtoupper(trim($value));
        $key = $language . ':' . $input;
        if (isset(self::$cache[$key])) return self::$cache[$key];
        self::$catalogue ??= json_decode(file_get_contents(__DIR__ . '/../../resources/countries.json'), true, 512, JSON_THROW_ON_ERROR);
        $countries = self::$catalogue['countries'] + self::$catalogue['territories'];
        $code = self::$catalogue['aliases'][$input] ?? $input;
        if (preg_match('/\A[A-Z]{3}\z/D', $code)) {
            foreach ($countries as $alpha2 => $row) if (($row['alpha3'] ?? null) === $code) { $code = $alpha2; break; }
        }
        $source = $countries[$code] ?? null;
        $name = $source && strlen($code) === 2 && class_exists(\Locale::class) ? \Locale::getDisplayRegion('und_' . $code, $language) : '';
        $result = ['code' => $code, 'name' => $name && $name !== $code ? $name : ($source['name'] ?? trim($value)), 'flag' => null];
        $result['name'] = $source['names'][$language] ?? $result['name'];
        try {
            PluginHelper::importPlugin('kluconcountries');
            $event = new Event('onKluconCountriesResolve', ['consumer'=>'com_joomleague', 'code'=>$code, 'language'=>$language, 'country'=>null]);
            Factory::getApplication()->getDispatcher()->dispatch('onKluconCountriesResolve', $event);
            $provided = $event->getArgument('country');
            if (is_array($provided) && ($provided['code'] ?? null) === $code && is_string($provided['name'] ?? null)) {
                $result['name'] = $provided['name'];
                $flag = $provided['flag'] ?? '';
                if (is_string($flag) && self::safeFlag($flag)) $result['flag'] = $flag;
            }
        } catch (\Throwable $error) {
            // Optional providers must not break text-only display.
        }
        return self::$cache[$key] = $result;
    }

    public static function name(string $value): string { return self::resolve($value)['name']; }

    private static function safeFlag(string $flag): bool
    {
        $supplied = preg_match('~\Amedia/com_kluconcountries/images/[a-z0-9/_-]+\.svg\z~D', $flag);
        $uploaded = preg_match('~\Aimages/com_kluconcountries/(?:continent|country|territory|region|district|capital_territory|municipality|locality|subdivision)/[a-f0-9]{8}(?:-[a-f0-9]{4}){3}-[a-f0-9]{12}/flags/[a-f0-9]{64}\.(?:svg|png)\z~D', $flag);
        if (!$supplied && !$uploaded) return false;
        $root = realpath(JPATH_ROOT);
        $base = realpath(JPATH_ROOT . ($supplied ? '/media/com_kluconcountries/images' : '/images/com_kluconcountries'));
        $path = realpath(JPATH_ROOT . '/' . $flag);
        return $root && $base && $path && str_starts_with($base, $root . DIRECTORY_SEPARATOR)
            && str_starts_with($path, $base . DIRECTORY_SEPARATOR) && is_file($path) && !is_link(JPATH_ROOT . '/' . $flag);
    }

    public static function html(string $value): string
    {
        $country = self::resolve($value);
        $name = htmlspecialchars($country['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        if (!$country['flag']) return $name;
        $url = htmlspecialchars(Uri::root(true) . '/' . $country['flag'], ENT_QUOTES, 'UTF-8');
        return '<span class="d-inline-flex align-items-center gap-1"><img src="' . $url . '" alt="" width="24" height="16" style="object-fit:contain" loading="lazy">' . $name . '</span>';
    }
}
