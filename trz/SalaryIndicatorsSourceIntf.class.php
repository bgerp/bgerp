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
 */
class trz_SalaryIndicatorsSourceIntf
{
    
    
    /**
     * Метод за изпращане на факсове
     */
    function getSalaryIndicators($periodId) 
    {
        return $this->class->getSalaryIndicators($periodId);
    }
}