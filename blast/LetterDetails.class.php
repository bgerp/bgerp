<?php



/**
 * Клас 'blast_LetterDetails' - Детайл на циркулярните писма
 *
 *
 * @category  bgerp
 * @package   blast
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class blast_LetterDetails extends doc_Detail
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'blast_Wrapper, plg_RowNumbering, plg_RowTools2, plg_Select, plg_Created, plg_Sorting, plg_State, plg_PrevAndNext, plg_SaveAndNew';
    
    
    /**
     * Заглавие
     */
    var $title = "Детайл на писма";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'blast,ceo';
    
    
    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'blast,ceo';
    
    
    /**
     * Кой може да го възстанови?
     */
    var $canRestore = 'blast,ceo';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'blast, ceo';
    
    
    /**
     * Кой  може да пише?
     */
    var $canWrite = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = 'Контакт за масово разпращане';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'letterId';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    //var $rowToolsField = 'RowNumb';
    
    
    /**
     * Брой записи на страница
     */
    var $listItemsPerPage = 10;
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'listDetailsId, printedDate, print=Печат';
    
    
    /**
     * Описание на полетата на модела
     */
    function description()
    {
        $this->FLD('letterId', 'key(mvc=blast_Letters, select=subject)', 'caption=Заглавие');
        $this->FLD('listDetailsId', 'keylist(mvc=blast_ListDetails, select=id)', 'caption=До:');
        $this->FLD('printedDate', 'datetime', 'caption=Отпечатано на, input=none');
    }
    
    
    /**
     * Добавя бутон на файловете, за печатане
     */
    static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        // Бутон за принтиране
        $row->print = HT::createBtn('Печат', array('blast_Letters', 'print', 'detailId' => $rec->id, 'Printing' => 'yes'),
            FALSE, array('target' => '_blank'), array('class' => 'print'));
        
        // Масив с всички детайли
        $listDetArr = type_Keylist::toArray($rec->listDetailsId);
        
        // Инстанция на мастъра
        $masterClass = cls::get($mvc->masterClass);
        
        // Външния ключ към мастъра
        $masterKey = $mvc->masterKey;
        
        // Вземаме записите
        $masterRec = $masterClass->fetch($rec->{$masterKey});
        
        // Обхождаме масива
        foreach ($listDetArr as $listDet) {
            
            // Ако има стринг, добавяме запетая
            $str .= ($str) ? ', ' : '';
            
            // Ако е лист
            if ($masterRec->listId) {
                
                // Вземаме записа за детайла
                $listDetRec = blast_ListDetails::fetch($listDet);
                
                // Вземаме името на полето
                $key = blast_ListDetails::getVerbal($listDetRec, 'key');
                
                // Добавяме към стринга
                $str .= ht::createLink($listDet, array('blast_ListDetails', 'edit', $listDet, 'ret_url' => TRUE), FALSE, array('title'=> $key));
            } elseif ($masterRec->group) {
                
                // Ако е грпа
                
                // Ако групата е фирм
                if ($masterRec->group == 'company') {
                    
                    // Ако имаме права към сингъла на фирмата
                    if (crm_Companies::haveRightFor('single', $listDet)) {
                        
                        // Вземаме записа
                        $cRec = crm_Companies::fetch($listDet);
                        
                        // Вземаме името на фирмата
                        $name = crm_Companies::getVerbal($cRec, 'name');
                        
                        // Добавяме линка към сингъла на фирмата в стринга
                        $str .= ht::createLink($listDet, array('crm_Companies', 'single', $listDet, 'ret_url' => TRUE), FALSE, array('title'=> $name));
                    } else {
                        
                        // Ако нямаме права добавяме само стринга
                        $str .= $listDet;
                    }
                } else {
                    
                    // Ако имаме права към сингъла на лицето
                    if (crm_Persons::haveRightFor('single', $listDet)) {
                        
                        // Вземаме записа
                        $pRec = crm_Persons::fetch($listDet);
                        
                        // Вземаме името мъ
                        $name = crm_Persons::getVerbal($pRec, 'name');
                        
                        // Добавяме линка към сингъла на лицето в стринга
                        $str .= ht::createLink($listDet, array('crm_Persons', 'single', $listDet, 'ret_url' => TRUE), FALSE, array('title'=> $name));
                    } else {
                        
                        // Ако нямаме права добавяме само стринга
                        $str .= $listDet;
                    }
                }
            }
        }
        
        // Добавяме стринга
        $row->listDetailsId = $str;
    }
}