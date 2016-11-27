<?php
namespace WPCivi\Shared\Gravity;

use WPCivi\Shared\Entity\Contact;

/**
 * Class Gravity\BackendEntityLink
 * Adds a link to a CiviCRM entity in the Gravity Forms backend for forms that handle a single CiviCRM entity.
 * Assumes the form handling class adds metadata values for 'wpcivi_status', 'wpcivi_entity' and 'wpcivi_entityid'.
 * @package WPCivi\Shared
 */
class BackendEntityLink extends BaseFormHandler
{

    /**
     * Register Gravity Forms backend entry view hooks.
     */
    public function register()
    {
        $this->addFilter('gform_entry_meta', [$this, 'entryMeta'], 10, 2);
        $this->addAction('gform_entries_column', [$this, 'entriesColumn'], 10, 5);
        $this->addAction('gform_entry_detail', [$this, 'entryDetail'], 10, 2);
    }

    /**
     * Implements hook gform_entry_meta.
     * Adds CiviCRM contact ID to form listings.
     * @param array $entry_meta Entry metadata
     * @param int $form_id Form ID
     * @return array Entry metadata
     */
    public function entryMeta($entry_meta, $form_id)
    {
        $entry_meta['wpcivi_entity'] = [
            'label'             => 'CiviCRM Entity',
            'is_numeric'        => false,
            'is_default_column' => false,
        ];
        $entry_meta['wpcivi_entityid'] = [
            'label'             => 'CiviCRM ID',
            'is_numeric'        => true,
            'is_default_column' => true,
        ];
        return $entry_meta;
    }

    /**
     * Implements hook gform_entries_column.
     * Adds a link to the CiviCRM contact ID column.
     * @param int $form_id Form ID
     * @param int $field_id Field ID
     * @param mixed $value Field value
     * @param array $entry Entry array
     * @param string $query_string Query string
     */
    public function entriesColumn($form_id, $field_id, $value, $entry, $query_string)
    {
        if (!empty($entry['wpcivi_entityid']) && $entry['wpcivi_entityid'] == $value) {
            echo '(' . $this->getEntityLink($entry['wpcivi_entity'], $entry['wpcivi_entityid'], true) . ')';
        }
    }

    /**
     * Implements hook gform_entry_detail.
     * Show status and link to CiviCRM contact, or show the error that occurred.
     * wr_success contains 'SUCCESS' or 'ERROR: Error Message'.
     * @param mixed $form Form
     * @param mixed $entry Entry
     * @return null
     */
    public function entryDetail($form, $entry)
    {
        $status = gform_get_meta($entry['id'], 'wpcivi_status');
        $entityType = gform_get_meta($entry['id'], 'wpcivi_entity');
        $entityId = gform_get_meta($entry['id'], 'wpcivi_entityid');

        if (!empty($status)) {

            echo "<div class='postbox'>\n<h3>CiviCRM Integration</h3>\n<div class='inside'>";
            if ($status == 'SUCCESS') {
                echo "This entry has been saved and can be deleted in Gravity Forms.<br>\n";
            } else {
                echo "This entry could <strong>NOT</strong> be correctly stored in CiviCRM.<br>\n<em>{$status}</em><br>\n";
            }

            echo $this->getEntityLink($entityType, $entityId, false);
            echo "</div></div>";
        }
    }

    /**
     * Get the actual ≤a href="http://link">Link</a> for entriesColumn and entryDetail.
     * @param string $entityType Entity Type
     * @param int $entityId Entity Id
     * @param bool $short Short output?
     * @return string Link HTML
     */
    private function getEntityLink($entityType, $entityId, $short = true)
    {
        switch ($entityType) {
            case 'Contact':
                $link = "/wp-admin/admin.php?page=CiviCRM&q=civicrm%2Fcontact%2Fview&cid={$entityId}&reset=1";
                break;
            case 'Case':
                $link = "/wp-admin/admin.php?page=CiviCRM&q=civicrm%2Fcontact%2Fview%2Fcase&action=view&selectedChild=case&id={$entityId}";
                $cid = Contact::getCurrentWPUserContactId();
                $link .= !empty($cid) ? "&cid=$cid" : "";
                break;
        }

        if (empty($link)) {
            return '';
        }

        $title = ($short ? "View {$entityType}" : "<strong>View CiviCRM {$entityType} (#{$entityId})</strong>");
        return "<a href='{$link}'>{$title}</a>";
    }

}