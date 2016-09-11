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
class Contact extends Entity
{

    /**
     * @var string Entity Type
     */
    protected $entityType = 'Contact';

    /**
     * Get contacts
     * @param array $params API parameters
     * @return EntityCollection Collection of Contact entities
     */
    public static function getContacts($params = [])
    {
        return EntityCollection::get('Contact', $params);
    }

    /**
     * Load contact by WordPress User ID
     * @param int $uf_id UFMatch ID (= WordPress User ID)
     * @return int Entity ID on success
     * @throws WPCiviException Thrown when contact does not exist
     */
    public function loadWPUser($uf_id)
    {
        $ufmatch = WPCiviApi::call('UFMatch', 'getsingle', ['uf_id' => $uf_id]);

        if (!empty($ufmatch->contact_id)) {
            return $this->load($ufmatch->contact_id);
        } else {
            throw new WPCiviException('Can\'t load Contact for WordPress user: contact does not exist.');
        }
    }

    /**
     * Load contact by current WordPress User ID
     * (we don't need to do a ufmatch search: we have user_contact_id...)
     * @return int Entity ID on success
     * @throws WPCiviException Thrown when user is not logged in or contact does not exist
     */
    public function loadCurrentWPUser()
    {
        try {
            return $this->load('user_contact_id');
        } catch (\Exception $e) {
            throw new WPCiviException('Can\'t load Contact by current WordPress user: user is not logged in.');
        }
    }

    /**
     * Get WordPress user for current contact (note that this currently means n+1 queries in a listing)
     * @return \WP_User|null WP User
     * @throws WPCiviException Thrown when no UFMatch record found
     */
    public function getWPUser()
    {
        $ufmatch = WPCiviApi::call('UFMatch', 'getsingle', ['contact_id' => $this->id]);
        if (!empty($ufmatch->uf_id)) {
            return get_user_by('id', $ufmatch->uf_id);
        } else {
            throw new WPCiviException('Can\'t load WordPress user: no UFMatch entry found.');
        }
    }

    /**
     * Get contact 'slug' (ie, a sanitized display name)
     * @return string Slug
     */
    public function getSlug()
    {
        return sanitize_title($this->display_name);
    }

    /**
     * Load full address data into this Entity object: ie full street address, phone / mobile separately, ...
     * @return void
     */
    public function loadFullAddressData()
    {
        $address = WPCiviApi::call('Address', 'get', ['contact_id' => $this->id, 'is_primary' => 1]);
        if(!$address->is_error && $address->count > 0) {
            $address = (array)$address->values;
            $this->full_address = array_shift($address);
            $this->street_name = $this->full_address->street_name;
            $this->street_number = $this->full_address->street_number;
            $this->street_unit = $this->full_address->street_unit;
        }

        $phone = WPCiviApi::call('Phone', 'get', [ 'contact_id' => $this->id, 'phone_type_id' => 'Phone']);
        if(!$phone->is_error && $phone->count > 0) {
            $phone = (array)$phone->values;
            $this->full_phone = array_shift($phone);
            $this->phone_phone = $this->full_phone->phone;
        }

        $mobile = WPCiviApi::call('Phone', 'get', [ 'contact_id' => $this->id, 'phone_type_id' => 'Mobile']);
        if(!$mobile->is_error && $mobile->count > 0) {
            $mobile = (array)$mobile->values;
            $this->full_mobile = array_shift($mobile);
            $this->phone_mobile = $this->full_mobile->phone;
        }
    }

}
