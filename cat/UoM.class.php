<?php


/**
 * Клас 'cat_UoM' - измервателни единици 
 *
 * Unit of Measures
 *
 * @category   Experta Framework
 * @package    cat
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class cat_UoM extends core_Manager
{
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_State, plg_RowTools, cat_Wrapper, plg_State2';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canDelete = 'no_one';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Измерителни единици';
    
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('name', 'varchar(36)', 'caption=Мярка, export');
        $this->FLD('shortName', 'varchar(12)', 'caption=Кратко име, export');
        $this->FLD('baseUnitId', 'key(mvc=cat_UoM, select=name,allowEmpty)', 'caption=Основна мярка, export');
        $this->FLD('baseUnitRatio', 'double', 'caption=Коефициент, export');
        
        $this->setDbUnique('name');
        $this->setDbUnique('shortName');
    }
    
    
    /**
     * @param double amount
     * @param int $unitId
     */
    function convertToBaseUnit($amount, $unitId)
    {
        $rec = $this->fetch($unitId);
        
        if ($rec->baseUnitId == null) {
            $ratio = 1;
        } else {
            $ratio = $rec->baseUnitRatio;
        }
        
        $result = $amount * $ratio;
        
        return $result;
    }
    
    
    /**
     * @param double amount
     * @param int $unitId
     */
    function convertFromBaseUnit($amount, $unitId)
    {
        $rec = $this->fetch($unitId);
        
        if ($rec->baseUnitId == null) {
            $ratio = 1;
        } else {
            $ratio = $rec->baseUnitRatio;
        }
        
        $result = $amount / $ratio;
        
        return $result;
    }
    
    
    /**
     * Инициализиране на таблицата при инсталиране с един запис
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     */
    function on_AfterSetupMvc($mvc, &$res)
    {
         
            $res .= cat_setup_UoM::setup();
    }
    
}