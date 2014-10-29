<?php



/**
 * Мениджър на дълготрайни активи
 *
 *
 * @category  bgerp
 * @package   accda
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Дълготрайни активи
 */
class accda_FixedAssets extends core_Master
{
    
    
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'accda_Da';
	
	
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'acc_RegisterIntf,accda_DaAccRegIntf';
    
    
    /**
     * Заглавие
     */
    public $title = 'Регистър на дълготрайните активи';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools, plg_SaveAndNew, plg_PrevAndNext, acc_plg_Registry, plg_Rejected, plg_State,
                     accda_Wrapper, plg_Sorting, plg_Printing, Groups=cat_Groups, doc_FolderPlg, plg_Select, plg_Search';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Fa';
    
    
    /**
     * Заглавие на единичен документ
     */
    public $singleTitle = 'Дълготраен актив';
    
    
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/doc_table.png';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,accda';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,accda';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,accda';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,accda';
    
    
    /**
     * Кой има достъп до сингъла
     */
    public $canSingle = 'ceo,accda';
    
    
    /**
     * Файл за единичен изглед
     */
    public $singleLayoutFile = 'accda/tpl/SingleLayoutFa.shtml';
    
    
    /**
     * Поле за търсене
     */
    public $searchFields = 'num, serial, title';
    
    
    /**
     * Кои полета ще се показват в списъчния изглед
     */
    public $listFields = 'tools=Пулт,title,num,serial,createdOn,createdBy';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('num', 'varchar(32)', 'caption=Номер->Наш, mandatory');
        
        $this->FLD('serial', 'varchar', 'caption=Номер->Сериен');
        
        $this->FLD('title', 'varchar', 'caption=Наименование,mandatory,width=400px');
        
        $this->FLD('info', 'text', 'caption=Описание,column=none,width=400px');
        
        $this->FLD('origin', 'text', 'caption=Произход,column=none,width=400px');
        
        $this->FLD('location', 'key(mvc=crm_Locations, select=title)', 'caption=Локация,column=none,width=400px');
        
        $this->FLD('inUseSince', 'date(format=d.m.Y)', 'caption=В употреба от');
        
        $this->FLD('amortNorm', 'percent', 'caption=ГАН,hint=Годишна амортизационна норма,notNull');
        
        $this->setDbUnique('num');
    }
    
    
    /**
     * Връща заглавието и мярката на перото за продукта
     *
     * Част от интерфейса: intf_Register
     */
    public static function getItemRec($objectId)
    {
        $result = NULL;
        $self = cls::get(get_called_class());
        
        if ($rec = self::fetch($objectId)) {
            $result = (object)array(
                'num' => $self->abbr . $rec->num,
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
    public static function itemInUse($objectId)
    {
        // @todo!
    }
}
