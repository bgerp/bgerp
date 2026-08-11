<?php


/**
 * Unit тестове за базовия клас
 *
 * @category  ef
 * @package   core
 *
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 */
class core_tests_BaseClass extends unit_Class
{
    /**
     * Нестатичните наследени listener-и се извикват в реда на наследяване
     */
    public static function test_InheritedNonStaticListeners()
    {
        $listener = new core_tests_BaseClassChildListener();
        $calls = array();
        $value = 0;

        $status = $listener->invoke('InheritedListener', array(&$calls, &$value));

        ut::expectEqual($status, true);
        ut::expectEqual($calls, array(
            'child:core_tests_BaseClassChildListener:same',
            'parent:core_tests_BaseClassChildListener:same',
            'grandparent:core_tests_BaseClassChildListener:same',
        ));
        ut::expectEqual($value, 111);
    }
}


class core_tests_BaseClassGrandParentListener extends core_BaseClass
{
    protected function on_InheritedListener($invoker, &$calls, &$value)
    {
        $calls[] = 'grandparent:' . get_class($this) . ':' . ($this === $invoker ? 'same' : 'different');
        $value += 1;
    }
}


class core_tests_BaseClassParentListener extends core_tests_BaseClassGrandParentListener
{
    protected function on_InheritedListener($invoker, &$calls, &$value)
    {
        $calls[] = 'parent:' . get_class($this) . ':' . ($this === $invoker ? 'same' : 'different');
        $value += 10;
    }
}


class core_tests_BaseClassChildListener extends core_tests_BaseClassParentListener
{
    protected function on_InheritedListener($invoker, &$calls, &$value)
    {
        $calls[] = 'child:' . get_class($this) . ':' . ($this === $invoker ? 'same' : 'different');
        $value += 100;
    }
}
