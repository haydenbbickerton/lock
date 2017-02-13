<?php
namespace BeatSwitch\Lock\Permissions;

use BeatSwitch\Lock\Lock;
use BeatSwitch\Lock\Targets\Target;

/**
 * A contract to define a permission rule, either a restriction or a privilege
 */
interface Permission
{
    /**
     * Validate a permission against the given params
     *
     * @param \BeatSwitch\Lock\Lock $lock
     * @param string $action
     * @param \BeatSwitch\Lock\Targets\Target|null $target
     * @return bool
     */
    public function isAllowed(Lock $lock, $action, Target $target = null);

    /**
     * Determine if a permission exactly matches the current instance
     *
     * @param \BeatSwitch\Lock\Permissions\Permission $permission
     * @return bool
     */
    public function matchesPermission(Permission $permission);

    /**
     * The type of permission, either "privilege" or "restriction"
     *
     * @return string
     */
    public function getType();

    /**
     * The action the permission is set for
     *
     * @return string
     */
    public function getAction();

    /**
     * The optional target an action should be checked on
     *
     * @return \BeatSwitch\Lock\Targets\Target|null
     */
    public function getTarget();

    /**
     * The target's type
     *
     * @return string|null
     */
    public function getTargetType();

    /**
     * The target's identifier
     *
     * @return int|null
     */
    public function getTargetId();
}
