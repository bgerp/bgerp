<?php

 /**
 * Интерфейс
 *
 * @category  bgerp
 * @package   trz
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Интерфейс за заплатите
 */
class trz_SalaryIndicatorsSourceIntf
{
    
    
    /**
     * Метод за изпращане на факсове
     */
    public function getSalaryIndicators($date) 
    {
        return $this->class->getSalaryIndicators($date);
    }
}