<?php
namespace BeatSwitch\Lock;

use BeatSwitch\Lock\Permissions\Permission;
use BeatSwitch\Lock\Targets\Target;
use BeatSwitch\Lock\Targets\SimpleTarget;
use BeatSwitch\Lock\Permissions\Privilege;
use BeatSwitch\Lock\Permissions\Restriction;

abstract class Lock
{
    /**
     * @var \BeatSwitch\Lock\Manager
     */
    protected $manager;

    /**
     * Determine if one or more actions are allowed
     *
     * @param string|array $action
     * @param string|\BeatSwitch\Lock\Targets\Target $target
     * @param int $targetId
     * @return bool
     */
    public function can($action, $target = null, $targetId = null)
    {
        $actions = (array) $action;
        $target = $this->convertTargetToObject($target, $targetId);
        $permissions = $this->getPermissions();

        foreach ($actions as $action) {
            if ($aliases = $this->getAliasesForAction($action)) {
                if ($this->can($aliases, $target) && $this->resolveRestrictions($permissions, $action, $target)) {
                    return true;
                }
            }

            if (! $this->resolvePermissions($action, $target)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if an action isn't allowed
     *
     * @param string|array $action
     * @param string|\BeatSwitch\Lock\Targets\Target $target
     * @param int $targetId
     * @return bool
     */
    public function cannot($action, $target = null, $targetId = null)
    {
        return ! $this->can($action, $target, $targetId);
    }

    /**
     * Give the subject permission to do something
     *
     * @param string|array $action
     * @param string|\BeatSwitch\Lock\Targets\Target $target
     * @param int $targetId
     * @param \BeatSwitch\Lock\Permissions\Condition|\BeatSwitch\Lock\Permissions\Condition[]|\Closure $conditions
     */
    public function allow($action, $target = null, $targetId = null, $conditions = [])
    {
        $actions = (array) $action;
        $target = $this->convertTargetToObject($target, $targetId);
        $permissions = $this->getPermissions();

        foreach ($actions as $action) {
            foreach ($permissions as $key => $permission) {
                if ($permission instanceof Restriction && ! $permission->isAllowed($this, $action, $target)) {
                    $this->removePermission($permission);
                    unset($permissions[$key]);
                }
            }

            // We'll need to clear any restrictions above
            $restriction = new Restriction($action, $target);

            if ($this->hasPermission($restriction)) {
                $this->removePermission($restriction);
            }

            $this->storePermission(new Privilege($action, $target, $conditions));
        }
    }

    /**
     * Deny the subject from doing something
     *
     * @param string|array $action
     * @param string|\BeatSwitch\Lock\Targets\Target $target
     * @param int $targetId
     * @param \BeatSwitch\Lock\Permissions\Condition|\BeatSwitch\Lock\Permissions\Condition[]|\Closure $conditions
     */
    public function deny($action, $target = null, $targetId = null, $conditions = [])
    {
        $actions = (array) $action;
        $target = $this->convertTargetToObject($target, $targetId);
        $permissions = $this->getPermissions();

        foreach ($actions as $action) {
            $this->clearPermission($action, $target, $permissions);

            $this->storePermission(new Restriction($action, $target, $conditions));
        }
    }

    /**
     * Change the value for a permission
     *
     * @param string|array $action
     * @param string|\BeatSwitch\Lock\Targets\Target $target
     * @param int $targetId
     */
    public function toggle($action, $target = null, $targetId = null)
    {
        if ($this->can($action, $target, $targetId)) {
            $this->deny($action, $target, $targetId);
        } else {
            $this->allow($action, $target, $targetId);
        }
    }

    /**
     * Returns the allowed ids which match the given action and target type
     *
     * @param string|array $action
     * @param string|\BeatSwitch\Lock\Targets\Target $targetType
     * @return array
     */
    public function allowed($action, $targetType)
    {
        $targetType = $targetType instanceof Target ? $targetType->getTargetType() : $targetType;

        // Get all the ids from privileges which match the given target type.
        $ids = array_unique(array_map(function (Permission $permission) {
            return $permission->getTargetId();
        }, array_filter($this->getPermissions(), function (Permission $permission) use ($targetType) {
            return $permission instanceof Privilege && $permission->getTargetType() === $targetType;
        })));

        return array_values(array_filter($ids, function ($id) use ($action, $targetType) {
            return $this->can($action, $targetType, $id);
        }));
    }

    /**
     * Returns the denied ids which match the given action and target type
     *
     * @param string|array $action
     * @param string|\BeatSwitch\Lock\Targets\Target $targetType
     * @return array
     */
    public function denied($action, $targetType)
    {
        $targetType = $targetType instanceof Target ? $targetType->getTargetType() : $targetType;

        // Get all the ids from restrictions which match the given target type.
        $ids = array_unique(array_map(function (Permission $permission) {
            return $permission->getTargetId();
        }, array_filter($this->getPermissions(), function (Permission $permission) use ($targetType) {
            return $permission instanceof Restriction && $permission->getTargetType() === $targetType;
        })));

        return array_values(array_filter($ids, function ($id) use ($action, $targetType) {
            return $this->cannot($action, $targetType, $id);
        }));
    }

    /**
     * Clear a given permission on a subject
     *
     * @param string|array $action
     * @param string|\BeatSwitch\Lock\Targets\Target $target
     * @param int $targetId
     */
    public function clear($action = null, $target = null, $targetId = null)
    {
        $actions = (array) $action;
        $targetObject = $this->convertTargetToObject($target, $targetId);
        $permissions = $this->getPermissions();

        if ($action === null && $target === null) {
            // Clear every permission for this lock instance.
            foreach ($permissions as $permission) {
                $this->removePermission($permission);
            }
        } elseif ($action === null && $target !== null) {
            // Clear all permissions for a given target.
            /** @todo Needs to be implemented */
        } else {
            // Clear every permission for the given actions.
            foreach ($actions as $action) {
                $this->clearPermission($action, $targetObject, $permissions);
            }
        }
    }

    /**
     * @param string $action
     * @param \BeatSwitch\Lock\Targets\Target $target
     * @param \BeatSwitch\Lock\Permissions\Permission[] $permissions
     */
    private function clearPermission($action, Target $target, array $permissions)
    {
        foreach ($permissions as $key => $permission) {
            if ($permission instanceof Privilege && $permission->isAllowed($this, $action, $target)) {
                $this->removePermission($permission);
                unset($permissions[$key]);
            }
        }

        $privilege = new Privilege($action, $target);

        if ($this->hasPermission($privilege)) {
            $this->removePermission($privilege);
        }
    }

    /**
     * Determine if an action is allowed
     *
     * @param string $action
     * @param \BeatSwitch\Lock\Targets\Target $target
     * @return bool
     */
    abstract protected function resolvePermissions($action, Target $target);

    /**
     * Check if the given restrictions prevent the given action and target to pass
     *
     * @param \BeatSwitch\Lock\Permissions\Permission[] $permissions
     * @param string $action
     * @param \BeatSwitch\Lock\Targets\Target $target
     * @return bool
     */
    protected function resolveRestrictions($permissions, $action, Target $target)
    {
        foreach ($permissions as $permission) {
            // If we've found a matching restriction, return false.
            if ($permission instanceof Restriction && ! $permission->isAllowed($this, $action, $target)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the given privileges allow the given action and target to pass
     *
     * @param \BeatSwitch\Lock\Permissions\Permission[] $permissions
     * @param string $action
     * @param \BeatSwitch\Lock\Targets\Target $target
     * @return bool
     */
    protected function resolvePrivileges($permissions, $action, Target $target)
    {
        // Search for privileges in the permissions.
        foreach ($permissions as $permission) {
            // If we've found a valid privilege, return true.
            if ($permission instanceof Privilege && $permission->isAllowed($this, $action, $target)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the permissions for the current subject
     *
     * @return \BeatSwitch\Lock\Permissions\Permission[]
     */
    abstract protected function getPermissions();

    /**
     * Stores a permission into the driver
     *
     * @param \BeatSwitch\Lock\Permissions\Permission $permission
     */
    abstract protected function storePermission(Permission $permission);

    /**
     * Removes a permission from the driver
     *
     * @param \BeatSwitch\Lock\Permissions\Permission $permission
     */
    abstract protected function removePermission(Permission $permission);

    /**
     * Checks if the subject has a specific permission
     *
     * @param \BeatSwitch\Lock\Permissions\Permission $permission
     * @return bool
     */
    abstract protected function hasPermission(Permission $permission);

    /**
     * Returns all aliases which contain the given action
     *
     * @param string $action
     * @return array
     */
    protected function getAliasesForAction($action)
    {
        $actions = [];

        foreach ($this->manager->getAliases() as $aliasName => $alias) {
            if ($alias->hasAction($action)) {
                $actions[] = $aliasName;
            }
        }

        return $actions;
    }

    /**
     * Create a target value object if a non target object is passed
     *
     * @param string|\BeatSwitch\Lock\Targets\Target|null $target
     * @param int|null $targetId
     * @return \BeatSwitch\Lock\Targets\Target
     */
    protected function convertTargetToObject($target, $targetId = null)
    {
        return ! $target instanceof Target ? new SimpleTarget($target, $targetId) : $target;
    }

    /**
     * Returns the current lock instant's subject
     *
     * @return object
     */
    abstract public function getSubject();

    /**
     * The current manager instance
     *
     * @return \BeatSwitch\Lock\Manager
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * The current driver provided by the manager
     *
     * @return \BeatSwitch\Lock\Drivers\Driver
     */
    public function getDriver()
    {
        return $this->manager->getDriver();
    }
}
