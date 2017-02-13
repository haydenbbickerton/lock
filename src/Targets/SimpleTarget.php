<?php
namespace BeatSwitch\Lock\Targets;

final class SimpleTarget implements Target
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var int|null
     */
    private $id;

    /**
     * @param string $type
     * @param int|null $id
     */
    public function __construct($type, $id = null)
    {
        $this->type = $type;
        $this->id = $id;
    }

    /**
     * The string value for the type of target
     *
     * @return string
     */
    public function getTargetType()
    {
        return $this->type;
    }

    /**
     * The main identifier for the target
     *
     * @return int|null
     */
    public function getTargetId()
    {
        return $this->id;
    }
}
