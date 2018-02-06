<?php
namespace WPCivi\Shared\Gravity\Field;

/**
 * Class WPCivi\Shared\Gravity\Field\CiviCountrySelect
 * Gravity form field type that populates a selectbox with countries from CiviCRM.
 * (It should be possible to simply copy this class and extend GF_Field_Checkbox or GF_Field_Radio.)
 * @package WPCivi\Shared
 */
class CiviCountrySelect extends \GF_Field_Select
{
    public $type = 'wpcivi-countryselect';
    public $type_label = 'CiviCRM Country Select';
    public $ogField = 'wpcivi_country';

    use CiviCountryFieldTrait;
}
