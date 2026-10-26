<?php
declare(strict_types=1);
namespace Klucon\Component\Kluconcountries\Administrator\Service;
defined('_JEXEC') or die;

final class Labels
{
    private static array $languages=[];
    public static function language(string $tag): string {return preg_match('/\A[a-z]{2,3}(?:-[A-Za-z0-9]{2,8}){1,3}\z/D',$tag)?$tag:'en-GB';}
    public static function get(string $key,string $english,string $tag): string {
        $tag=self::language($tag);if($tag==='en-GB')return $english;
        if(!isset(self::$languages[$tag])){
            $language=new \Joomla\CMS\Language\Language($tag);
            foreach(['com_kluconcountries.units','com_kluconcountries.countries','com_kluconcountries.continents','com_kluconcountries.facts'] as $file)$language->load($file,JPATH_ADMINISTRATOR,$tag,false,false);
            self::$languages[$tag]=$language;
        }
        return self::$languages[$tag]->hasKey($key)?self::$languages[$tag]->_($key):$english;
    }
}
