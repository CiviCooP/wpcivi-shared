<?php
namespace WPCivi\Shared\Entity;

use WPCivi\Shared\Civi\WPCiviApi;
use WPCivi\Shared\Entity;
use WPCivi\Shared\EntityCollection;

/**
 * Class Entity\Cases (since 'Case' is a PHP reserved word)
 * @property int $id
 * @property int $status_id
 * @property int $case_type_id
 * @property string $subject
 */
class Cases extends Entity
{
    /**
     * @var string Entity Type
     */
    protected $entityType = 'Case';

    /**
     * @var string[] Case option group cache
     */
    protected static $optionGroupCache = [];

    /**
     * Get cases
     * @param array $params API parameters
     * @return EntityCollection Collection of Case entities
     */
    public static function getCases($params = [])
    {
        return EntityCollection::get('Cases', $params);
    }

    /**
     * Get cases for current user
     * @return EntityCollection Collection of Case entities
     */
    public static function getCasesForCurrentUser()
    {
        return EntityCollection::get('Cases', [
            'contact_id' => 'user_contact_id',
        ]);
    }

    /**
     * Get possible case options
     * @param string $field Field Name
     * @return string[] Key-value-array of option keys/values for field
     */
    public static function getCaseOptions($field)
    {
        if(!isset(static::$optionGroupCache[$field]))
        {
            $res = WPCiviApi::call('Case', 'getoptions', ['field' => $field]);

            static::$optionGroupCache[$field] = [];
            foreach($res->values as $status)
            {
                static::$optionGroupCache[$field][$status->key] = $status->value;
            }
        }

        return static::$optionGroupCache[$field];
    }

    /**
     * Get case status name (instead of id) for this case
     * @return string|null Case status name
     */
    public function getCaseStatusName()
    {
        $statuses = static::getCaseOptions('case_status_id');
        if(array_key_exists($this->status_id, $statuses)) {
            return $statuses[$this->status_id];
        }
        return null;
    }

    /**
     * Get case contacts
     * @return mixed Case contacts
     */
    public function getContacts()
    {
        return $this->getValue('contacts');
    }

    /**
     * Get case activities (TODO - does not work, did it ever?)
     * @return mixed Case activities
     */
    public function getActivities()
    {
        $activities = $this->getValue('activities');
        if(is_array($activities) && count($activities) > 0) {
            $activities = EntityCollection::get('Activity', ['activity_id' => ['IN' => $activities]]);
            return $activities;
        }
        return null;
    }

    /**
     * Get case 'slug' (ie, a sanitized case subject)
     * return string Slug
     */
    public function getSlug() {
        return sanitize_title($this->subject);
    }

}
