<?php
namespace BeatSwitch\Lock\Permissions;

use BeatSwitch\Lock\Lock;
use BeatSwitch\Lock\Targets\Target;
use Closure;

abstract class AbstractPermission implements Permission
{
    /**
     * @var string
     */
    protected $action;

    /**
     * @var \BeatSwitch\Lock\Targets\Target|null
     */
    protected $target;

    /**
     * @var \BeatSwitch\Lock\Permissions\Condition[]|\Closure
     */
    protected $conditions;

    /**
     * @param string $action
     * @param \BeatSwitch\Lock\Targets\Target|null $target
     * @param \BeatSwitch\Lock\Permissions\Condition|\BeatSwitch\Lock\Permissions\Condition[]|\Closure $conditions
     */
    public function __construct($action, Target $target = null, $conditions = [])
    {
        $this->action = $action;
        $this->target = $target;
        $this->setConditions($conditions);
    }

    /**
     * Determine if a permission exactly matches the current instance
     *
     * @param \BeatSwitch\Lock\Permissions\Permission $permission
     * @return bool
     */
    public function matchesPermission(Permission $permission)
    {
        return (
            $this instanceof $permission &&
            $this->action === $permission->getAction() && // Not using matchesAction to avoid the wildcard
            $this->matchesTarget($permission->getTarget())
        );
    }

    /**
     * Validate a permission against the given params.
     *
     * @param \BeatSwitch\Lock\Lock $lock
     * @param string $action
     * @param \BeatSwitch\Lock\Targets\Target|null $target
     * @return bool
     */
    protected function resolve(Lock $lock, $action, Target $target = null)
    {
        // If no target was set for this permission we'll only need to check the action.
        if ($this->target === null || $this->target->getTargetType() === null) {
            return $this->matchesAction($action) && $this->resolveConditions($lock, $action, $target);
        }

        return (
            $this->matchesAction($action) &&
            $this->matchesTarget($target) &&
            $this->resolveConditions($lock, $action, $target)
        );
    }

    /**
     * Validate the action
     *
     * @param string $action
     * @return bool
     */
    protected function matchesAction($action)
    {
        return $this->action === $action || $this->action === 'all';
    }

    /**
     * Validate the target
     *
     * @param \BeatSwitch\Lock\Targets\Target|null $target
     * @return bool
     */
    protected function matchesTarget(Target $target = null)
    {
        // If the target is null we should only return true if the current target is also null.
        if ($target === null) {
            return $this->getTarget() === null || (
                $this->getTargetType() === null && $this->getTargetId() === null
            );
        }

        // If the permission's target id is null then all targets with a specific ID are accepted.
        if ($this->getTargetId() === null) {
            return $this->getTargetType() === $target->getTargetType();
        }

        // Otherwise make sure that we're matching a specific target.
        return (
            $this->getTargetType() === $target->getTargetType() &&
            $this->getTargetId() === $target->getTargetId()
        );
    }

    /**
     * Sets the conditions for this permission
     *
     * @param \BeatSwitch\Lock\Permissions\Condition|\BeatSwitch\Lock\Permissions\Condition[]|\Closure $conditions
     */
    protected function setConditions($conditions = [])
    {
        if ($conditions instanceof Closure || is_array($conditions)) {
            $this->conditions = $conditions;
        } else {
            $this->conditions = [$conditions];
        }
    }

    /**
     * Check all the conditions and make sure they all return true
     *
     * @param \BeatSwitch\Lock\Lock $lock
     * @param string $action
     * @param \BeatSwitch\Lock\Targets\Target|null $target
     * @return bool
     */
    protected function resolveConditions(Lock $lock, $action, $target)
    {
        // If the given condition is a closure, execute it.
        if ($this->conditions instanceof Closure) {
            return call_user_func($this->conditions, $lock, $this, $action, $target);
        }

        // If the conditions are an array of Condition objects, check them all.
        foreach ($this->conditions as $condition) {
            if (! $condition->assert($lock, $this, $action, $target)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @return \BeatSwitch\Lock\Targets\Target|null
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * The target's type
     *
     * @return string|null
     */
    public function getTargetType()
    {
        return $this->target ? $this->target->getTargetType() : null;
    }

    /**
     * The target's identifier
     *
     * @return int|null
     */
    public function getTargetId()
    {
        return $this->target ? $this->target->getTargetId() : null;
    }
}
