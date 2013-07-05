<?php



/**
 * Мениджър на групи от дълготрайни активи
 *
 *
 * @category  bgerp
 * @package   accda
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     ДА Групи
 */
class accda_Groups extends core_Manager
{
    
    
    /**
     * Кой линк от главното меню на страницата да бъде засветен?
     */
    var $menuPage = 'Счетоводство';
    
    
    /**
     * Заглавие
     */
    var $title = 'ДА Групи';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, plg_Created, plg_SaveAndNew, 
                    accda_Wrapper';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,accda';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo,accda';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,accda';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'ceo,accda';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,accda';
    
    
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