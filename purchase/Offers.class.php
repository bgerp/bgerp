<?php



/**
 * Мениджър на оферти за покупки
 *
 *
 * @category  bgerp
 * @package   purchase
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Оферти за покупки
 */
class purchase_Offers extends core_Master
{
    
    
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf';
    
    
    /**
     * Абревиатура
     */
    var $abbr = 'Pqt';
    
    
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
    var $canRead = 'ceo,purchase';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo,purchase';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo,purchase';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'ceo,purchase';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo,purchase';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'tools=Пулт';
        
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    /**
     * Поле за търсене
     */
    var $searchFields = 'folderId, threadId, containerId';
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "4.1|Логистика";
    
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
        requireRole('purshase, admin');
        
    	$text = tr('В процес на разработка');
    	$underConstructionImg = "<h2>$text</h2><img src=". sbf('img/under_construction.png') .">";

        return $this->renderWrapping($underConstructionImg);
    }
    
    
    /**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща данните за адресанта
     */
    static function getContragentData($id)
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
        $tpl = new ET(tr("Предлагаме на вашето внимание нашата оферта:") . '\n[#handle#]');
        
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
