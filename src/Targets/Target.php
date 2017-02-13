<?php
namespace BeatSwitch\Lock\Targets;

/**
 * A contract to identify a target which can be used to set permissions on
 */
interface Target
{
    /**
     * The string value for the type of target
     *
     * @return string
     */
    public function getTargetType();

    /**
     * The main identifier for the target
     *
     * @return int|null
     */
    public function getTargetId();
}
