<?php



/**
 * Интерфейс за персонални графици - организационна структура
 *
 *
 * @category  bgerp
 * @package   hr
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Интерфейс за персонални работни графици - организационна структура
 */
class hr_SpecialDaySourceIntf
{
    /**
     * Мотод, който взима специалните дни по графиците
     * 
     * 
     * @param $timeline $timeline  $datetime    Времето, след което да се вземат всички модифицирани/създадени графици
     */
    public function getSpecialDayValues($timeline)
    {
        return $this->class->getSpecialDayValues($timeline);
    }
}