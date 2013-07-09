<?php



/**
 * Мениджър на регистър на транспортните документи CMR, B/L
 *
 *
 * @category  bgerp
 * @package   transport
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     регистър на транспортните документи CMR, B/L
 */
class transport_Registers extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Регистър на транспортните документи CMR, B/L';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, plg_Created, plg_SaveAndNew, 
                    transport_Wrapper';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,transport';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo,transport';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,transport';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'ceo,transport';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,transport';
    
    
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
    	$text = tr('В процес на разработка');
    	$underConstructionImg = "<h2>$text</h2><img src=". sbf('img/under_construction.png') .">";

        return $this->renderWrapping($underConstructionImg);
    }
}