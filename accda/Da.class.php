<?php


/**
 * Мениджър на дълготрайни активи
 *
 *
 * @category  bgerp
 * @package   accda
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Дълготрайни активи
 */
class accda_Da extends core_Master
{
    
    
    /**
     * Интерфайси, поддържани от този мениджър
     */
    var $interfaces = 'acc_RegisterIntf,accda_DaAccRegIntf';
    
    
    /**
     * Заглавие
     */
    var $title = 'Регистър на дълготрайните активи';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, accda_Wrapper, plg_State2, plg_Printing,
                     acc_plg_Registry, plg_Sorting, plg_SaveAndNew';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin,accda';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'admin,accda';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin,accda';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'admin,accda';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'admin,accda';
    /**
     * @todo Чака за документация...
     */
    var $canSingle = 'admin,accda';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('num', 'int', 'caption=Наш номер, mandatory');
        
        $this->FLD('serial', 'varchar', 'caption=Сериен номер');
        
        $this->FLD('title', 'varchar', 'caption=Наименование,mandatory,width=400px');
        
        $this->FLD('info', 'text', 'caption=Описание,column=none,width=400px');
        
        $this->FLD('origin', 'text', 'caption=Произход,column=none,width=400px');
        
        $this->FLD('inUseSince', 'date', 'caption=В употреба от');
        
        $this->FLD('amortNorm', 'double', 'caption=ГАН,hint=Годишна амортизационна норма,unit=%');
        
        $this->setDbUnique('num');
    }
    
    
    /**
     * Връща заглавието и мярката на перото за продукта
     *
     * Част от интерфейса: intf_Register
     */
    function getItemRec($objectId)
    {
        $result = NULL;
        
        if ($rec = self::fetch($objectId)) {
            $result = (object)array(
                'num' => $rec->num,
                'title' => $rec->title,
                'features' => 'foobar' // @todo!
            );
        }
        
        return $result;
    }
    
    
    /**
     * @see crm_ContragentAccRegIntf::itemInUse
     * @param int $objectId
     */
    static function itemInUse($objectId)
    {
        // @todo!
        }
}