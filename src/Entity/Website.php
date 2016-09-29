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
     * @return EntityCollection|[] Collection of Website entities, or key/value array of websites
     */
    public static function getWebsitesForContact($contact_id = null, $full = false)
    {
        $types = static::getWebsiteTypes();
        $websites = WPCiviApi::call('Website', 'get', ['contact_id' => $contact_id]);

        $ret = $full ? new EntityCollection('Website') : [];
        if ($websites->is_error) {
            return $ret;
        }

        foreach ((array)$websites->values as $w) {
            $type = $types[$w->website_type_id];
            if ($full) {
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
     * @param array $websites Array of websites, with website type as key and website URL as value
     * @throws WPCiviException Thrown if an unexpected error occurs
     * @return void
     */
    public static function setWebsitesForContact($contact_id = null, $websites = [])
    {
        $old_websites = static::getWebsitesForContact($contact_id, false);
        foreach ($websites as $type => $url) {
            if(!is_string($url) || empty($url)) {
                continue;
            }

            if (array_key_exists($type, $old_websites)) {
                if (is_object($old_websites[$type])) {
                    $current_site = $old_websites[$type];
                } else {
                    $current_site = new Website;
                    try {
                        // Load existing website entity if not given
                        $current_site->loadBy(['contact_id' => $contact_id, 'website_type_id' => $type, 'options' => ['limit' => 1]]);
                    } catch (WPCiviException $e) {
                        // We'll try to create a new one, then
                    }
                }
            } else {
                $current_site = new Website;
            }

            $current_site->website_type_id = $type;
            $current_site->url = $url;
            $current_site->contact_id = $contact_id;
            $current_site->save();
        }
    }

}
