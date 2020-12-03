<?php


/**
 *
 *
 * @category  bgerp
 * @package   acs
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class acs_ZoneIntf
{
    
    
    /**
     * Връща списък с наименованията на всички четци за които отговаря
     * 
     * @return array
     */
    public function getCheckpoints()
    {
        
        return $this->class->getCheckpoints($rec);
    }
    
    
    /**
     * 
     * @param string $chp - име на четеца
     * @param array $perm - масив с номер на карта и таймстамп на валидност
     */
    public function setPermissions($chp, $perm)
    {
        
        return $this->class->setPermissions($chp, $perm);
    }
}
