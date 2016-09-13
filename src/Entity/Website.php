<?php
namespace WPCivi\Shared\Entity;

use WPCivi\Shared\Civi\WPCiviApi;
use WPCivi\Shared\Civi\WPCiviException;
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
     * @param bool $full Whether to get a full Website object or just the URL as array value
     * @return EntityCollection Collection of Website entities
     */
    public static function getWebsitesForContact($contact_id = null, $full = false)
    {
        $types = static::getWebsiteTypes();
        $websites = WPCiviApi::call('Website', 'get', ['contact_id' => $contact_id]);

        if ($websites->is_error) {
            return [];
        }

        $ret = [];
        foreach ((array)$websites->values as $w) {
            $type = $types[$w->website_type_id];
            if($full) {
                $ret[$type] = $w;
            } else {
                $ret[$type] = $w->url;
            }
        }
        return $ret;
    }

    /**
     * Set websites for a contact. Adds if a website (type) does not exist, replaces if it does and has changed.
     * @param int $contact_id Contact ID
     * @param string[] $websites Array of websites, with website type as key and website URL as value
     * @throws WPCiviException Thrown if an unexpected error occurs
     * @return void
     */
    public static function setWebsitesForContact($contact_id = null, $websites = [])
    {
        $old_websites = self::getWebsitesForContact($contact_id);
        foreach($websites as $type => $url)
        {
            if(array_key_exists($type, $old_websites)) {
                $old_websites[$type]->url = $url;
                $old_websites[$type]->save();
            } else {
                $site = new static;
                $site->website_type_id = $type;
                $site->url = $url;
                $site->contact_id = $contact_id;
                $site->save();
            }
        }
    }

}
