<?php


/**
 * Детайли на изгледите
 *
 *
 * @category  bgerp
 * @package   floor
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class floor_ViewDetails extends core_Detail {


    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'viewId';


   /**
     * Необходими плъгини
     */
    public $loadList = 'plg_Created, plg_RowTools2, plg_State2, plg_Rejected, floor_Wrapper, plg_SaveAndNew, plg_StructureAndOrder';
    
    
    /**
     * Заглавие
     */
    public $title = 'Планове в изглед';
    

    /**
     * Заглавие в единичния изглед
     */
    public $singleTitle = 'План в изглед';
    

    /**
     * Права за писане
     */
    public $canWrite = 'floor,admin,ceo';
    
    
    /**
     * Права за запис
     */
    public $canRead = 'floor,admin,ceo';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'floor,admin,ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'floor,admin,ceo';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'floor,admin,ceo';
    
      
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/wooden-box.png';
    
      
    /**
     * Полета, които ще се показват в листов изглед
     */
    // public $listFields = 'order,name,state';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('viewId', 'key(mvc=floor_Views,select=name)', 'caption=Изглед, mandatory,column=none');
        $this->FLD('planId', 'key(mvc=floor_Plans,select=name)', 'caption=План, mandatory');
        $this->setDbUnique('viewId, planId');
    }


    /**
     * Необходим метод за подреждането
     */
    public static function getSaoItems($rec)
    {
        $res = array();
        $query = self::getQuery();
        $query->where(array("#viewId = '[#1#]'", $rec->viewId));
        while ($rec = $query->fetch()) {
            $res[$rec->id] = $rec;
        }

        return $res;
    }
}