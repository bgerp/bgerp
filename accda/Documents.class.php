<?php



/**
 * Мениджър на документи за дълготрайни активи
 *
 *
 * @category  bgerp
 * @package   accda
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     ДА Документи
 */
class accda_Documents extends core_Master
{
    
    
    /**
     * Кой линк от главното меню на страницата да бъде засветен?
     */
    public $menuPage = 'Счетоводство';
    
    
    /**
     * Заглавие
     */
    public $title = 'ДА Документи';
    
    
    /**
     * Заглавието в единствено число
     */
    public $singleTitle = 'Протокол за промяна на ДА';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, doc_DocumentPlg, plg_SaveAndNew, 
                    accda_Wrapper, plg_Search';
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    public $onlyFirstInThread = true;
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,accda';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,accda';
    
    
    /**
     * Кой има права за сингъла на документа
     */
    public $canSingle = 'ceo,accda';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,accda';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го види?
     */
    public $canView = 'ceo,accda';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,accda';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, docType, tools=Пулт';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Dac';
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = false;
    
    
    /**
     * Поле за търсене
     */
    public $searchFields = 'folderId, threadId, containerId';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = '6.3|Счетоводни';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('docType', 'enum(SR=протокол за въвеждане в експлоатация,
                                       EN=протокол за промяна,
                                       IM=амортизационен план,
                                       OOP=протокол за ликвидация)', 'caption=Тип документ');
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
        $folderClass = doc_Folders::fetchCoverClassName($folderId);
        
        return $folderClass == 'store_Stores';
    }
    
    
    /**
     * Интерфейсен метод на doc_DocumentIntf
     */
    public function getDocumentRow($id)
    {
        if (!$id) {
            return;
        }
        
        $rec = $this->fetch($id);
        
        $row = new stdClass();
        $row->title = $rec->title;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
        $row->authorId = $rec->createdBy;
        $row->recTitle = $rec->title;
        
        return $row;
    }
}
