<?php
namespace BeatSwitch\Lock\Permissions;

use BeatSwitch\Lock\Lock;
use BeatSwitch\Lock\Targets\Target;

/**
 * A contract to define a permission condition. Conditions need to give
 * back a true value if a permission is to succeed.
 */
interface Condition
{
    /**
     * Assert if the condition is correct
     *
     * @param \BeatSwitch\Lock\Lock $lock
     * @param \BeatSwitch\Lock\Permissions\Permission $permission
     * @param string $action
     * @param \BeatSwitch\Lock\Targets\Target|null $target
     * @return bool
     */
    public function assert(Lock $lock, Permission $permission, $action, Target $target = null);
}
