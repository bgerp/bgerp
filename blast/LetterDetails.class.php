<?php

/**
 * Клас 'blast_LetterDetails' - Детайл на циркулярните писма
 * 
 * @category   bgERP
 * @package    blast
 * @author     Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 3
 * @since      v 0.1
 */
class blast_LetterDetails extends core_Detail
{
    var $loadList = 'blast_Wrapper, plg_RowNumbering, plg_RowTools, plg_Select, plg_Created, plg_Sorting, plg_State';

    var $title    = "Детайл на писма";

    var $canRead   = 'blast,admin';
    var $canReject = 'blast,admin';
    var $canDelete = 'blast, admin';
    
    var $canWrite  = 'no_one';
    var $canAdd = 'no_one';
    var $canEdit = 'no_one'; 

    var $singleTitle = 'Контакт за масово разпращане';

    var $masterKey = 'letterId';

    var $rowToolsField = 'RowNumb';

    var $listItemsPerPage = 10;
	
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
	 * 
	 * Добавя бутон на файловете, които са за клишета
	 */
	function on_AfterRecToVerbal($mvc, &$row, $rec)
	{
        $row->print = HT::createBtn('Печат', array('blast_Letters', 'print', $rec->id, 'Printing' => 'yes'), 
        		FALSE, array('target' => '_blank'), array('class' => 'print'));         
	}
	
	
	/**
     * Преди извличане на записите подрежда ги по дата на отпечатване и състояние
     */
    function on_BeforePrepareListRecs($mvc, $res, &$data)
    {    	
        $data->query->orderBy('#state', 'ASC');
        $data->query->orderBy('#printedDate', 'DESC');
        
        return ;
    }
}