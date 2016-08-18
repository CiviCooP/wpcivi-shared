<?php
namespace WPCivi\Shared\Entity;

use WPCivi\Shared\Entity;

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

    public function loadWPUser($uf_id) {
        // TODO: implement function loadWPUser
    }
    public function loadCurrentWPUser() {
        // TODO: implement function loadCurrentWPUser
    }

    public function isCurrentMember() {
        // TODO: implement function isCurrentMember (bool)
    }

    public function getMemberships() {
        // TODO: implement function getMemberships
    }

}
