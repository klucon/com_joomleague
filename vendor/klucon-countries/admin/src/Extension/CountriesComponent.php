<?php
namespace Klucon\Component\Kluconcountries\Administrator\Extension;
defined('_JEXEC') or die;
use Joomla\CMS\Extension\MVCComponent;
use Klucon\Component\Kluconcountries\Administrator\Service\CountryDirectory;

final class CountriesComponent extends MVCComponent
{
    private CountryDirectory $countries;
    public function setCountryDirectory(CountryDirectory $countries): void { $this->countries = $countries; }
    public function getCountry(string $code, string $language = 'en-GB',?string $date=null): ?array
    {
        return $this->countries->getCountry($code, $language,$date);
    }
    public function getTerritory(string $uuid,string $language='en-GB',?string $date=null): ?array {return $this->countries->getTerritory($uuid,$language,$date);}
    public function getChildren(string $uuid,string $language='en-GB',?string $date=null): array {return $this->countries->getChildren($uuid,$language,$date);}
    public function getContinents(string $language='en-GB',?string $date=null): array {return $this->countries->getContinents($language,$date);}
    public function findTerritories(string $scheme,string $code,string $language='en-GB',?string $date=null): array {return $this->countries->findTerritories($scheme,$code,$language,$date);}
}
