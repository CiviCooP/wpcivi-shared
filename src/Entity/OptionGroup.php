<?php
namespace WPCivi\Shared\Entity;

use WPCivi\Shared\Entity;
use WPCivi\Shared\EntityCollection;

/**
 * Class Entity\OptionGroup.
 * @package WPCivi\Shared
 */
class OptionGroup extends Entity
{

    /**
     * @var string Entity Type
     */
    protected $entityType = 'OptionGroup';

    /**
     * Get active (user created) option groups for this installation
     * @return EntityCollection Collection of OptionGroup entities
     */
    public static function getCustomOptionGroups()
    {
        return EntityCollection::get('OptionGroup', [
            'is_active' => 1,
            'name'    => ['LIKE' => '%_20%'],     // Quick hack: user created groups contain _20160101...
            'options' => ['sort' => 'id DESC'],   // Show most recent first
        ]);
    }
}
