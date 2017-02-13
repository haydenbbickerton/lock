<?php
namespace BeatSwitch\Lock;

/**
 * This trait can be used on objects which extend the Caller or Role contract.
 * After setting the Lock instance with the setLock method, the object receives
 * the ability to call the public api from the lock instance onto itself.
 */
trait LockAware
{
    /**
     * The current object's lock instance
     *
     * @var \BeatSwitch\Lock\Lock
     */
    private $lock;

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
        $this->assertLockInstanceIsSet();

        return $this->lock->can($action, $target, $targetId);
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
        $this->assertLockInstanceIsSet();

        return $this->lock->cannot($action, $target, $targetId);
    }

    /**
     * Give a caller permission to do something
     *
     * @param string|array $action
     * @param string|\BeatSwitch\Lock\Targets\Target $target
     * @param int $targetId
     * @param \BeatSwitch\Lock\Permissions\Condition|\BeatSwitch\Lock\Permissions\Condition[]|\Closure $conditions
     */
    public function allow($action, $target = null, $targetId = null, $conditions = [])
    {
        $this->assertLockInstanceIsSet();

        $this->lock->allow($action, $target, $targetId, $conditions);
    }

    /**
     * Deny a caller from doing something
     *
     * @param string|array $action
     * @param string|\BeatSwitch\Lock\Targets\Target $target
     * @param int $targetId
     * @param \BeatSwitch\Lock\Permissions\Condition|\BeatSwitch\Lock\Permissions\Condition[]|\Closure $conditions
     */
    public function deny($action, $target = null, $targetId = null, $conditions = [])
    {
        $this->assertLockInstanceIsSet();

        $this->lock->deny($action, $target, $targetId, $conditions);
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
        $this->assertLockInstanceIsSet();

        $this->lock->toggle($action, $target, $targetId);
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
        $this->assertLockInstanceIsSet();

        return $this->lock->allowed($action, $targetType);
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
        $this->assertLockInstanceIsSet();

        return $this->lock->denied($action, $targetType);
    }

    /**
     * Clear a given permission on a subject
     *
     * @param string|array $action
     * @param string|\BeatSwitch\Lock\Targets\Target $target
     * @param int $targetId
     */
    public function clear($action, $target = null, $targetId = null)
    {
        $this->assertLockInstanceIsSet();

        $this->lock->clear($action, $target, $targetId);
    }

    /**
     * Sets the lock instance for this caller
     *
     * @param \BeatSwitch\Lock\Lock $lock
     * @throws \BeatSwitch\Lock\InvalidLockInstance
     */
    public function setLock(Lock $lock)
    {
        // Make sure that the subject from the given lock instance is this object.
        if ($lock->getSubject() !== $this) {
            throw new InvalidLockInstance('Invalid Lock instance given for current object.');
        }

        $this->lock = $lock;
    }

    /**
     * Makes sure that a valid lock instance is set before an api method is called
     *
     * @throws \BeatSwitch\Lock\LockInstanceNotSet
     */
    private function assertLockInstanceIsSet()
    {
        if (! $this->lock instanceof Lock) {
            throw new LockInstanceNotSet(
                'Please set a valid lock instance on this class before attempting to use it.'
            );
        }
    }
}
