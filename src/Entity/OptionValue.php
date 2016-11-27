<?php
namespace WPCivi\Shared\Entity;

use WPCivi\Shared\Entity;
use WPCivi\Shared\EntityCollection;

/**
 * Class Entity\OptionValue.
 * @package WPCivi\Shared
 */
class OptionValue extends Entity
{

    /**
     * @var string Entity Type
     */
    protected $entityType = 'OptionValue';

    /**
     * Get option values for an option group by name
     * @return EntityCollection Collection of OptionValue entities
     */
    public static function getOptionValues($optionGroupName)
    {
        return EntityCollection::get('OptionValue', [
            'option_group_id' => $optionGroupName,
            'is_active' => 1,
            'options' => ['sort' => 'weight ASC'],
        ]);
    }
}
