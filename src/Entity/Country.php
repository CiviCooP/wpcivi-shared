<?php
namespace WPCivi\Shared\Entity;

use WPCivi\Shared\Entity;
use WPCivi\Shared\EntityCollection;

/**
 * Class Entity\Country.
 * @package WPCivi\Shared
 */
class Country extends Entity
{
    /**
     * @var string Entity Type
     */
    protected $entityType = 'Country';

    /**
     * Get a list of countries for use in selects, with NL on top.
     * @return EntityCollection Collection of Country entities
     */
    public static function getCountries()
    {
        $countries = EntityCollection::get('Country', [
            'options' => ['sort' => 'name ASC'],
        ]);

        $countries->usort(function($a, $b) {
            if($a->iso_code == 'NL') {
                return -1;
            } elseif($b->iso_code == 'NL') {
                return 1;
            }

            return strcasecmp($a->name, $b->name);
        });

        return $countries;
    }
}
