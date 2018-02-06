<?php
namespace WPCivi\Shared\Gravity\Field;

/**
 * Class WPCivi\Shared\Gravity\Field\CiviOGSelect
 * Gravity form field type that populates a selectbox with OptionValues from CiviCRM.
 * (It should be possible to simply copy this class and extend GF_Field_Checkbox or GF_Field_Radio.)
 * @package WPCivi\Shared
 */
class CiviOGSelect extends \GF_Field_Select
{
    public $type = 'wpcivi-ogselect';
    public $type_label = 'CiviCRM OptionGroup Select';
    public $ogField = 'wpcivi_optiongroup';

    use CiviOGFieldTrait;
}
