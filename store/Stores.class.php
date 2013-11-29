<?php



/**
 * Складове
 *
 * Мениджър на складове
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @TODO      Това е само примерна реализация за тестване на продажбите. Да се вземе реализацията от bagdealing.
 */
class store_Stores extends core_Master
{
    
    
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'store_AccRegIntf, acc_RegisterIntf, store_TransferFolderCoverIntf';
    
    
    /**
     * Заглавие
     */
    var $title = 'Складове';
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Склад";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, plg_Created, acc_plg_Registry, store_Wrapper, plg_Current, plg_Rejected, doc_FolderPlg';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,store';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo,store';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,store';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'ceo,store';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,store';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,store';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,acc';
    
    
    /**
     * Кой може да пише
     */
    var $canWrite = 'ceo,storeMaster';
    
    
    /**
	 * Кой може да селектира всички записи
	 */
	var $canSelectAll = 'ceo,storeMaster';
	
	
   /**
	* Кой може да селектира?
	*/
	var $canSelect = 'ceo,store';
	
	
    /**
     * Брой записи на страница
     */
    var $listItemsPerPage = 300;
    
    
    /**
	 * Кое поле отговаря на кой работи с даден склад
	 */
	var $inChargeField = 'chiefId';
	
	
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, name, chiefId';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'name';
    
    
    /**
     * var $rowToolsField = 'tools';
     */
    var $autoList = 'stores';
    
    
    /**
     * Икона за единичен изглед
     */
    var $singleIcon = 'img/16/home-icon.png';
    
    
    /**
     * Файл с шаблон за единичен изглед
     */
    var $singleLayoutFile = 'store/tpl/SingleLayoutStore.shtml';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('name', 'varchar(128)', 'caption=Име,mandatory,remember=info');
        $this->FLD('comment', 'varchar(256)', 'caption=Коментар');
        $this->FLD('chiefId', 'key(mvc=core_Users, select=names)', 'caption=Отговорник,mandatory');
        $this->FLD('workersIds', 'userList(store,storeWorker,ceo)', 'caption=Товарачи');
        $this->FLD('strategy', 'class(interface=store_ArrangeStrategyIntf)', 'caption=Стратегия');
    }
    
    
    /**
     * Имплементация на @see intf_Register::getAccItemRec()
     */
    static function getAccItemRec($rec)
    {
        return (object)array(
            'title' => $rec->name
        );
    }
    
    /*******************************************************************************************
     * 
     * ИМПЛЕМЕНТАЦИЯ на интерфейса @see crm_ContragentAccRegIntf
     * 
     ******************************************************************************************/
    
    
    /**
     * @see crm_ContragentAccRegIntf::getItemRec
     * @param int $objectId
     */
    static function getItemRec($objectId)
    {
        $self = cls::get(__CLASS__);
        $result = NULL;
        
        if ($rec = $self->fetch($objectId)) {
            $result = (object)array(
                'num' => $rec->id,
                'title' => $rec->name,
                'features' => 'foobar' // @todo!
            );
        }
        
        return $result;
    }
    
    
    /**
     * @see crm_ContragentAccRegIntf::getLinkToObj
     * @param int $objectId
     */
    static function getLinkToObj($objectId)
    {
        $self = cls::get(__CLASS__);
        
        if ($rec = $self->fetch($objectId)) {
            $result = ht::createLink(static::getVerbal($rec, 'name'), array($self, 'Single', $objectId));
        } else {
            $result = '<i>неизвестно</i>';
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
    
    
    /**
     * КРАЙ НА интерфейса @see acc_RegisterIntf
     */
	
    
	/**
	 * Преди подготовка на резултатите
	 */
	function on_BeforePrepareListRecs($mvc, $res, $data)
	{
		if(!haveRole('ceo,storeMaster')){
			
			// Показват се само записите за които отговаря потребителя
			$cu = core_Users::getCurrent();
			$data->query->where("#chiefId = {$cu}");
		}
	}


	static function on_AfterPrepareEditForm($mvc, &$res, $data)
	{
		// Ако сме в тесен режим
		if (Mode::is('screenMode', 'narrow')) {
	
			// Да има само 2 колони
			$data->form->setField('workersIds', array('maxColumns' => 2));
		}
	}
}