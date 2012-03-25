<?php



/**
 * Мениджър на оферти за покупки
 *
 *
 * @category  all
 * @package   purchase
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Оферти за покупки
 */
class purchase_Offers extends core_Manager
{
    
    
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf';
    
    
    /**
     * Абревиатура
     */
    var $abbr = 'PQT';
    
    /**
     * Заглавие на единичен документ
     */
    var $singleTitle = 'Оферта от доставчик';


    /**
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/doc_table.png';
    
    
    /**
     * Заглавие
     */
    var $title = 'Оферти за покупки';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, plg_Rejected, plg_State2, plg_SaveAndNew, 
                    purchase_Wrapper, doc_DocumentPlg, doc_EmailCreatePlg, doc_ActivatePlg';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin,purchase';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'admin,purchase';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin,purchase';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'admin,purchase';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'admin,purchase';
    
    
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
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща данните за адресанта
     */
    function getContragentData($id)
    {
        //TODO
        
        return $contragentData;
    }
    
    
    /**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща тялото наимей по подразбиране
     */
    static function getDefaultEmailBody($id)
    {
        //TODO
        $handle = purchase_Offers::getHandle($id);
        
        //Създаваме шаблона
        $tpl = new ET(tr("Предлагаме на вашето внимание нашата оферта:\n") . '[#handle#]');
        
        //Заместваме датата в шаблона
        $tpl->append($handle, 'handle');
        
        return $tpl->getContent();
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getDocumentRow($id)
    {
        //TODO
        
        return $row;
    }
}