<?php



/**
 * Клас 'cond_TaxAndFees' - Данъци и такси
 *
 *
 * @category  bgerp
 * @package   cond
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cond_TaxAndFees extends core_Manager
{
    
	
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'cond_TaxAndFeesRegIntf';

    
    /**
     * Заглавие
     */
    public $title = "Данъци и такси";
    
    
    /**
     * Заглавие на единичния обект
     */
    public $singleTitle = 'Данък и такса';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools2, plg_State2, cond_Wrapper, acc_plg_Registry';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, title, type, state';
    
    
    /**
     * Кой може да променя?
     */
    public $canWrite = 'ceo,admin';
    
    
    /**
     * Кой може да променя състоянието на валутата
     */
    public $canChangestate = 'ceo,admin';
    

    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,admin';
    
    
    /**
     * Всички записи на този мениджър автоматично стават пера в номенклатурата със системно име
     * $autoList.
     *
     * @see acc_plg_Registry
     * @var string
     */
    public $autoList = 'taxes';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('title', 'varchar(255)', 'caption=Наименование');
        $this->FLD('type', 'enum(local=Местен,republican=Републикански,another=Друг)', 'caption=Вид,value=local,tdClass=centerCol');
        $this->FLD('state', 'enum(active=Активен,closed=Затворен,)', 'caption=Видимост,input=none,notSorting,notNull,value=active');
         
        $this->setDbUnique('title');
    }

    
    /**
     * @see crm_ContragentAccRegIntf::getItemRec
     * @param int $objectId
     */
    public static function getItemRec($objectId)
    {
        $self = cls::get(__CLASS__);
        $result = NULL;
    
        if ($rec = $self->fetch($objectId)) {
            $result = (object)array(
                'num' => $rec->id . " tf",
                'title' => $rec->title,
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