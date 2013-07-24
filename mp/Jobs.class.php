<?php



/**
 * Мениджър на Задания за производство
 *
 *
 * @category  bgerp
 * @package   mp
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Задания за производство
 */
class mp_Jobs extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Задания за производство';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, plg_Created, plg_Rejected, mp_Wrapper';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,mp';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo,mp';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,mp';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,mp';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,mp';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'ceo,mp';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,mp';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'tools=Пулт';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    }
    
    
    /**
     * Екшън по подразбиране.
     * Извежда картинка, че страницата е в процес на разработка
     */
    function act_Default()
    {
        requireRole('mp, admin');
        
    	$text = tr('В процес на разработка');
    	$underConstructionImg = "<h2>$text</h2><img src=". sbf('img/under_construction.png') .">";

        return $this->renderWrapping($underConstructionImg);
    }
}