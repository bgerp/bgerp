<?php



/**
 * Мениджър на глоби и премии
 *
 *
 * @category  bgerp
 * @package   trz
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Глоби и Премии
 */
class trz_Fines extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Глоби';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, plg_Created, plg_Rejected, plg_State2, plg_SaveAndNew, 
                    trz_Wrapper, doc_plg_BusinessDoc';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,trz';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,trz';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,trz';
    
    
    /**
     * Кой може да го види?
     */
    public $canView = 'ceo,trz';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,trz';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,trz';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,trz';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=Пулт';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    }
    
    /**
     * Екшън по подразбиране.
     * Извежда картинка, че страницата е в процес на разработка
     */
    public function act_Default()
    {
        requireRole('trz, admin');
        
    	$text = tr('В процес на разработка');
    	$underConstructionImg = "<h2>$text</h2><img src=". sbf('img/under_construction.png') .">";

        return $this->renderWrapping($underConstructionImg);
    }
    
    
    /**
     * В кои корици може да се вкарва документа
     * @return array - интерфейси, които трябва да имат кориците
     */
    public static function getAllowedFolders()
    {
    	return array('crm_PersonAccRegIntf');
    }
}