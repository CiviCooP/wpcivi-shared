<?php
namespace WPCivi\Shared\Entity;

use WPCivi\Shared\Civi\WPCiviException;
use WPCivi\Shared\Entity;
use WPCivi\Shared\Util\CustomConfigCache;

/**
 * Class Entity\Phone
 * @package WPCivi\Shared
 */
class Phone extends Entity
{

    /**
     * @var string Entity Type
     */
    protected $entityType = 'Phone';

    /**
     * Default parameters for new phone numbers
     */
    protected function setDefaults()
    {
        $this->setArray([
            'location_type_id' => CustomConfigCache::getInstance()->getDefaultLocationTypeId(),
            'is_primary'       => 1,
        ]);
    }

    /**
     * Shortcut function for quickly creating a new phone number (with standard options)
     * @param int $contactId
     * @param string $phoneNumber
     * @param string $phoneType
     * @param int $locationType
     * @return bool Success
     * @throws WPCiviException
     */
    public static function createPhone($contactId, $phoneNumber, $phoneType = 'Mobile', $locationType = null) {

        $phone = new self;
        $phone->contact_id = $contactId;
        $phone->phone_type_id = $phoneType;
        $phone->phone = $phoneNumber;

        if(!empty($locationType)) {
            $phone->location_type_id = $locationType;
        }
        return $phone->save();
    }
}
