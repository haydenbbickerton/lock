<?php
namespace spec\BeatSwitch\Lock\Drivers;

use BeatSwitch\Lock\Callers\SimpleCaller;
use BeatSwitch\Lock\Permissions\Privilege;
use BeatSwitch\Lock\Targets\SimpleTarget;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ArrayDriverSpec extends ObjectBehavior
{
    /**
     * @var \BeatSwitch\Lock\Callers\Caller
     */
    protected $caller;

    function let()
    {
        $this->caller = new SimpleCaller('users', 1);

        $this->storeCallerPermission($this->caller, new Privilege('read'));
        $this->storeCallerPermission($this->caller, new Privilege('edit', new SimpleTarget('users', 1)));
        $this->storeCallerPermission($this->caller, new Privilege('manage', new SimpleTarget('tasks')));
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('BeatSwitch\Lock\Drivers\ArrayDriver');
        $this->shouldImplement('BeatSwitch\Lock\Drivers\Driver');
    }

    function it_returns_caller_permissions()
    {
        $this->getCallerPermissions($this->caller)->shouldHaveCount(3);
    }

    function it_stores_a_caller_permission()
    {
        $this->storeCallerPermission($this->caller, new Privilege('create', new SimpleTarget('events')));
        $this->getCallerPermissions($this->caller)->shouldHaveCount(4);
    }

    function it_removes_a_caller_permission()
    {
        $this->removeCallerPermission($this->caller, new Privilege('manage', new SimpleTarget('tasks')));
        $this->getCallerPermissions($this->caller)->shouldHaveCount(2);
    }

    function it_can_confirm_it_has_a_caller_permission()
    {
        $this->hasCallerPermission($this->caller, new Privilege('manage', new SimpleTarget('tasks')))->shouldReturn(true);
        $this->hasCallerPermission($this->caller, new Privilege('edit', new SimpleTarget('events')))->shouldReturn(false);
    }
}
