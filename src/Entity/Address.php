<?php
namespace WPCivi\Shared\Entity;

use WPCivi\Shared\Entity;
use WPCivi\Shared\Util\CustomConfigCache;

/**
 * Class Entity\Address.
 * @package WPCivi\Shared
 */
class Address extends Entity
{
    /**
     * @var string Entity Type
     */
    protected $entityType = 'Address';

    /**
     * Default parameters for new entities
     */
    protected function setDefaults()
    {
        $ccc = CustomConfigCache::getInstance();
        $this->setArray([
            'country_id'       => $ccc->getDefaultCountryId(),
            'location_type_id' => $ccc->getDefaultLocationTypeId(),
            'is_primary'       => 1,
        ]);
    }

}
