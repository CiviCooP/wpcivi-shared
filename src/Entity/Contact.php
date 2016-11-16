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
        if(!isset($this->slug)) {
            $this->slug = sanitize_title($this->display_name, $this->id);
        }
        return $this->slug;
    }

    /**
     * Get the primary Address entity for this contact using loadBy, or return an empty entity if no address is set
     * @return Address Address Entity
     */
    public function getAddress()
    {
        if (!isset($this->address_entity) || !is_object($this->address_entity)) {
            $this->address_entity = new Address;
            try {
                $this->address_entity->loadBy(['contact_id' => $this->id, 'is_primary' => 1, 'options' => ['limit' => 1]]);
                if (!empty($this->address_entity->id)) {
                    // Load existing
                    $this->street_name = $this->address_entity->street_name;
                    $this->street_number = $this->address_entity->street_number;
                    $this->street_unit = $this->address_entity->street_unit;
                } else {
                    // Init new
                    $this->address_entity->contact_id = $this->id;
                    $this->address_entity->is_primary = 1;
                    $this->address_entity->location_type_id = 'Work';
                }
            } catch (WPCiviException $e) {}
        }
        return $this->address_entity;
    }

    /**
     * Get the first (Landline) Phone entity for this contact using loadBy, or return an empty entity if not set
     * @return Phone Phone Entity
     */
    public function getPhone()
    {
        if (!isset($this->phone_entity) || !is_object($this->phone_entity)) {
            $this->phone_entity = new Phone;
            try {
                $this->phone_entity->loadBy(['contact_id' => $this->id, 'phone_type_id' => 'Phone', 'options' => ['limit' => 1]]);
                if (empty($this->phone_entity->id)) {
                    throw new WPCiviException('Invalid existing record.');
                }
                $this->phone_no = $this->mobile_entity->phone;
            } catch (WPCiviException $e) {
                $this->phone_entity->contact_id = $this->id;
                $this->phone_entity->phone_type_id = 'Phone';
                $this->phone_entity->location_type_id = 'Work';
            }
        }
        return $this->phone_entity;
    }

    /**
     * Get the first Mobile Phone entity for this contact using loadBy, or return an empty entity if not set
     * @return Phone Mobile Phone Entity
     */
    public function getMobile()
    {
        if (!isset($this->mobile_entity) || !is_object($this->mobile_entity)) {
            $this->mobile_entity = new Phone;
            try {
                $this->mobile_entity->loadBy(['contact_id' => $this->id, 'phone_type_id' => 'Mobile',
                                              'options' => ['limit' => 1]]);
                if (empty($this->mobile_entity->id)) {
                    throw new WPCiviException('Invalid existing record.');
                }
                $this->mobile_no = $this->mobile_entity->phone;
            } catch (WPCiviException $e) {
                $this->mobile_entity->contact_id = $this->id;
                $this->mobile_entity->phone_type_id = 'Mobile';
                $this->mobile_entity->location_type_id = 'Work';
            }
        }
        return $this->mobile_entity;
    }

    /**
     * Get the primary Email entity for this contact using loadBy, or return an empty entity if not set
     * @return Email Email Entity
     */
    public function getEmail()
    {
        if (!isset($this->email_entity) || !is_object($this->email_entity)) {
            $this->email_entity = new Email;
            try {
                // Load existing
                $this->email_entity->loadBy(['contact_id' => $this->id, 'is_primary' => 1, 'options' => ['limit' => 1]]);
                if (empty($this->email_entity->id)) {
                    throw new WPCiviException('Invalid existing record.');
                }
            } catch (WPCiviException $e) {
                // Init new
                $this->email_entity->contact_id = $this->id;
                $this->email_entity->location_type_id = 'Work';
                $this->email_entity->is_primary = 1;
            }
        }
        return $this->email_entity;
    }

}
