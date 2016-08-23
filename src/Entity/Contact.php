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
    public static function getContacts($params = []) {

        return EntityCollection::get('Contact', $params);
    }

    /**
     * Load contact by WordPress User ID
     * @param int $uf_id UFMatch ID (= WordPress User ID)
     * @return int Entity ID on success
     * @throws WPCiviException Thrown when contact does not exist
     */
    public function loadWPUser($uf_id) {

        $wpcivi = WPCiviApi::getInstance();
        $ufmatch = $wpcivi->api('UFMatch', 'getsingle', ['uf_id' => $uf_id]);

        if(!empty($ufmatch->contact_id)) {
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
    public function loadCurrentWPUser() {
        try {
            return $this->load('user_contact_id');
        } catch(\Exception $e) {
            throw new WPCiviException('Can\'t load Contact by current WordPress user: user is not logged in.');
        }
    }

    /**
     * Get WordPress user for current contact
     * @return \WP_User|null WP User
     * @throws WPCiviException Thrown when no UFMatch record found
     */
    public function getWPUser() {

        $wpcivi = WPCiviApi::getInstance();
        $ufmatch = $wpcivi->api('UFMatch', 'getsingle', ['contact_id' => $this->id]);
        if(!empty($ufmatch->uf_id)) {
            return get_user_by( 'id', $ufmatch->uf_id );
        } else {
            throw new WPCiviException('Can\'t load WordPress user: no UFMatch entry found.');
        }
    }

}
