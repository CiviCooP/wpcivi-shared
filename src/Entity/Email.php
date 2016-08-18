<?php
namespace WPCivi\Shared\Entity;

use WPCivi\Shared\Civi\WPCiviException;
use WPCivi\Shared\Entity;
use WPCivi\Shared\Util\CustomConfigCache;

/**
 * Class Entity\Email.
 * @package WPCivi\Shared
 */
class Email extends Entity
{

    /**
     * @var string Entity Type
     */
    protected $entityType = 'Email';

    /**
     * Default parameters for new email addresses
     */
    protected function setDefaults()
    {
        $this->setArray([
            'location_type_id' => CustomConfigCache::getInstance()->getDefaultLocationTypeId(),
            'is_primary'       => 1,
        ]);
    }

    /**
     * Shortcut function to quickly add a new email address (with default options)
     * @param int $contactId
     * @param string $emailAddress
     * @param int $locationType
     * @return bool Success
     * @throws WPCiviException
     */
    public static function createEmail($contactId, $emailAddress, $locationType = null)
    {
        $email = new self;
        $email->contact_id = $contactId;
        $email->email = $emailAddress;

        if(!empty($locationType)) {
            $email->setValue('location_type_id', $locationType);
        }
        return $email->save();
    }
}