<?php

/**
 * Клас 'blast_LetterDetails' - 
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
    var $loadList = 'blast_Wrapper,plg_RowNumbering,plg_RowTools,plg_Select,expert_Plugin, plg_Created, plg_Sorting';

    var $title    = "Детайл на писма";

    var $canRead   = 'blast,admin';
    var $canReject = 'blast,admin';
    var $canDelete = 'blast, admin';
    
    var $canWrite  = 'admin'; //no_one
    var $canAdd = 'admin'; //no_one
    var $canEdit = 'admin'; //no_one

    var $singleTitle = 'Контакт за масово разпращане';

    var $masterKey = 'letterId';

    var $rowToolsField = 'RowNumb';

    var $listItemsPerPage = 100;
	
    var $listFields = 'id, listDetailsId, printedDate, print=Принтиране';

    
    /**
     * Описание на полетата на модела
     */
    function description()
    {
        // Информация за папката
        //$this->FLD('lettersId' ,  'key(mvc=blast_Letters,select=title)', 'caption=Списък,mandatory,column=none');
		$this->FLD('letterId', 'key(mvc=blast_Letters, select=subject)', 'caption=Заглавие');
		$this->FLD('listDetailsId', 'keylist(mvc=blast_ListDetails, select=id)', 'caption=До:');
		$this->FLD('printedDate', 'datetime', 'caption=Отпечатано на, input=none');

//        $this->setDbUnique('letterId, listDetailsId');
    }

    
	/**
	 * 
	 * Добавя бутон на файловете, които са за клишета
	 */
	function on_AfterRecToVerbal($mvc, &$row, $rec)
	{
        $row->print = HT::createBtn('Печат', array('blast_Letters', 'print', $rec->id, 'Printing' => 'yes'), 
        		FALSE, array('target' => '_blank'), array('class' => 'print'));

//            $r = type_Keylist::toArray($rec->listDetailsId);
//        if (count($r)) {
//            $row->listDetailsId = '';
//            foreach ($r as $value) {
//                $row->listDetailsId .= $value;
//            }
//        }
         
	}

}