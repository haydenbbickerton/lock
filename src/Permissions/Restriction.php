<?php
namespace BeatSwitch\Lock\Permissions;

use BeatSwitch\Lock\Lock;
use BeatSwitch\Lock\Targets\Target;

/**
 * A restriction is placed when you deny a caller something
 */
class Restriction extends AbstractPermission implements Permission
{
    /** @var string */
    const TYPE = 'restriction';

    /**
     * Validate a permission against the given params
     *
     * @param \BeatSwitch\Lock\Lock $lock
     * @param string $action
     * @param \BeatSwitch\Lock\Targets\Target|null $target
     * @return bool
     */
    public function isAllowed(Lock $lock, $action, Target $target = null)
    {
        return ! $this->resolve($lock, $action, $target);
    }

    /**
     * @return string
     */
    public function getType()
    {
        return self::TYPE;
    }
}
