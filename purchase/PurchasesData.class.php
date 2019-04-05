<?php


/**
 * Модел за извадка от данни за покупките
 *
 *
 * @category  bgerp
 * @package   purchase
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class purchase_PurchasesData extends core_Manager
{
    /**
     * Себестойности към документ
     */
    public $title = 'Извадка от данни за покупките';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'purchase_Wrapper,plg_AlignDecimals2,plg_Sorting';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces ;
    
    
    /**
     * Кой може да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да редактира?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin,ceo,debug';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,valior=Вальор,containerId,productId,quantity,sellCost,primeCost,delta,dealerId,initiatorId,state,isPublic,folderId';
    
    
    /**
     * Работен кеш
     */
    public static $cache = array();
    
    
    /**
     * Работен кеш
     */
    public static $groupNames = array();
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('valior', 'date(smartTime)', 'caption=Вальор,mandatory');
        $this->FLD('detailClassId', 'class(interface=core_ManagerIntf)', 'caption=Детайл,mandatory');
        $this->FLD('detailRecId', 'int', 'caption=Ред от детайл,mandatory, tdClass=leftCol');
        $this->FLD('containerId', 'int', 'caption=Документ,mandatory');
        $this->FLD('productId', 'int', 'caption=Артикул,mandatory, tdClass=productCell leftCol wrap');
        $this->FLD('quantity', 'double', 'caption=Количество,mandatory');
        $this->FLD('price', 'double', 'caption=Цени->Пукупна,mandatory');
        $this->FLD('amount', 'double', 'caption=Цени->Стойност,mandatory');
        $this->FLD('dealerId', 'user', 'caption=Дилър,mandatory');
        $this->FLD('initiatorId', 'user', 'caption=Инициатор,mandatory');
        $this->FLD('state', 'enum(draft=Чернова, active=Контиран, rejected=Оттеглен,stopped=Спряно,pending=Заявка,closed=Затворено)', 'caption=Статус, input=none');
        $this->FLD('folderId', 'int', 'caption=Папка,tdClass=leftCol');
        $this->FLD('threadId', 'int', 'caption=Нишка,tdClass=leftCol');
        $this->FLD('contragentId', 'int', 'caption=Контрагент,tdClass=leftCol');
        $this->FLD('contragentClassId', 'int', 'caption=Контрагент');
        
        $this->setDbIndex('productId,containerId');
        $this->setDbIndex('productId');
        $this->setDbIndex('containerId');
        $this->setDbIndex('folderId');
        $this->setDbIndex('detailClassId,detailRecId,productId');
    }
    
    
   
    
    
  
}
