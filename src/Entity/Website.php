<?php
namespace WPCivi\Shared\Entity;

use WPCivi\Shared\Civi\WPCiviApi;
use WPCivi\Shared\Entity;
use WPCivi\Shared\EntityCollection;

/**
 * Class Entity\Contact.
 * @package WPCivi\Shared
 */
class Website extends Entity
{

    /**
     * @var string Entity Type
     */
    protected $entityType = 'Website';

    /**
     * Get list of website types
     * @return array Key-value array of website types
     */
    public static function getWebsiteTypes()
    {
        return static::getStaticCache('website_type', function () {
            $data = WPCiviApi::call('Website', 'getoptions', ['field' => 'website_type_id', 'sequential' => 0]);
            if (!$data->is_error) {
                $ret = []; // Ik geef op:
                foreach ((array)$data->values as $k => $v) {
                    $ret[(int)$k] = $v;
                }
                return $ret;
            }
        });
    }

    /**
     * Get list of websites for a contact
     * @param int $contact_id Contact ID
     * @return EntityCollection Collection of Website entities
     */
    public static function getWebsitesForContact($contact_id = null)
    {
        $types = static::getWebsiteTypes();
        $websites = WPCiviApi::call('Website', 'get', ['contact_id' => $contact_id]);

        if ($websites->is_error) {
            return [];
        }

        $ret = [];
        foreach ((array)$websites->values as $w) {
            $type = $types[$w->website_type_id];
            $ret[$type] = $w->url;
        }
        return $ret;
    }

}
