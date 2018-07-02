<?php


/**
 * Клас Агрегатор на бизнес данни по сделките, Инстанцира се в Модел с интерфейс 'bgerp_DealAggregatorIntf',
 * и се предава в документите от неговата нишка, те му сетват пропъртита ако няма.
 *
 *
 * @category  bgerp
 *
 * @author    Ivelin Dimov <ivelin_pdimov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bgerp_iface_DealAggregator
{
    /**
     * Масив с издадените фактури към момента.
     */
    public $invoices = array();

    /**
     * Задава стойност на пропърти, ако няма.
     *
     * @param string $name  - име на пропърти
     * @param string $value - стойност
     */
    public function setIfNot($name, $value)
    {
        // Ако няма стойност пропъртито, задаваме му първата стойност
        if (empty($this->{$args[0]})) {
            $this->{$name} = $value;
        }
    }

    /**
     * Задава стойност на пропърти.
     *
     * @param string $name  - име на пропърти
     * @param string $value - стойност
     */
    public function set($name, $value)
    {
        $this->{$name} = $value;
    }

    /**
     * Връща стойност на пропърти.
     *
     * @param string $name - име на пропърти
     *
     * @return mixed
     */
    public function get($name)
    {
        // Връщаме стойността на пропъртито
        return $this->{$name};
    }

    /**
     * Добавя/изважда сума от пропърти на обекта.
     *
     * @param string $name  - име на пропърти
     * @param mixed  $value - стойност за добавяне / изваждане
     */
    public function sum($name, $value)
    {
        // Добавяме към стойността на пропъртито
        $this->{$name} += $value;
    }

    /**
     * Пушва стойност към масив.
     *
     * @param string $name  - име на пропърти
     * @param mixed  $array - масив, обект или скалар който да се добавя към масива
     */
    public function push($name, $array, $index = null)
    {
        // Ако няма такова пропърти, създаваме го
        if (!isset($this->{$name})) {
            $this->{$name} = array();
        }

        if ($index) {
            $a = &$this->{$name};
            $a[$index] = $array;
        } else {
            // Долепяме в края на масива
            array_push($this->{$name}, $array);
        }
    }

    /**
     * Добавя стойност към масив от масиви.
     *
     * @param string $name  - име на пропърти
     * @param mixed  $array - масив, обект или скалар който да се добавя към масива
     */
    public function pushToArray($name, $array, $index)
    {
        // Ако няма такова пропърти, създаваме го
        if (!isset($this->{$name})) {
            $this->{$name} = array();
        }

        $a = &$this->{$name};
        $a[$index][] = $array;
    }
}
