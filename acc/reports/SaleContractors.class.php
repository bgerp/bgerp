<?php



/**
 * Мениджър на отчети от Приходи от продажби по продукти
 * Имплементация на 'frame_ReportSourceIntf' за направата на справка на баланса
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_reports_SaleContractors extends acc_reports_BalanceImpl
{


	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'acc_SaleContractorsReport';
	
	
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectSource = 'ceo, acc';


    /**
     * Заглавие
     */
    public $title = 'Счетоводство » Приходи от продажби по клиенти';


    /**
     * Дефолт сметка
     */
    public $accountSysId = '701';
    
    
    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 50;


    /**
     * След подготовката на ембеднатата форма
     */
    public static function on_AfterAddEmbeddedFields($mvc, core_FieldSet &$form)
    {

        // Искаме да покажим оборотната ведомост за сметката на касите
        $accId = acc_Accounts::getRecBySystemId($mvc->accountSysId)->id;
        $form->setDefault('accountId', $accId);
        $form->setHidden('accountId');

        // Дефолт периода е текущия ден
        $today = dt::today();

        $form->setDefault('from',date('Y-m-01', strtotime("-1 months", dt::mysql2timestamp(dt::now()))));
        $form->setDefault('to', $today);
        
        unset($form->fields['orderField']->type->options['ent2Id']);
        unset($form->fields['orderField']->type->options['ent3Id']);
        unset($form->fields['orderField']->type->options['baseQuantity']);
        unset($form->fields['orderField']->type->options['baseAmount']);
        unset($form->fields['orderField']->type->options['debitQuantity']);
        unset($form->fields['orderField']->type->options['debitAmount']);
        unset($form->fields['orderField']->type->options['creditQuantity']);
        unset($form->fields['orderField']->type->options['blAmount']);
        unset($form->fields['orderField']->type->options['blQuantity']);
        
        $form->fields['orderField']->type->options['ent1Id'] = "Контрагенти";
        $form->fields['orderField']->type->options['creditAmount'] = "Сума";
        
        $form->setDefault('orderField', 'creditAmount');
        // Задаваме, че ще филтрираме по перо
        $form->setDefault('action', 'group');

        $form->setDefault('orderBy', 'desc');
    }


    /**
     * След подготовката на ембеднатата форма
     */
    public static function on_AfterPrepareEmbeddedForm($mvc, core_Form &$form)
    {
        $form->setHidden('action');

        foreach (range(1, 3) as $i) {
            $form->setHidden("feat{$i}");
            $form->setHidden("grouping{$i}");

        }

        $articlePositionId = acc_Lists::getPosition($mvc->accountSysId, 'crm_ContragentAccRegIntf');

        $form->setDefault("feat{$articlePositionId}", "*");
    }


    public static function on_AfterGetReportLayout($mvc, &$tpl)
    {
        $tpl->removeBlock('action');
    }


    public static function on_AfterPrepareListFields($mvc, &$res, &$data)
    {

        unset($data->listFields['baseQuantity']);
        unset($data->listFields['baseAmount']);
        unset($data->listFields['debitQuantity']);
        unset($data->listFields['debitAmount']);
        unset($data->listFields['blQuantity']);
        unset($data->listFields['blAmount']);
        unset($data->listFields['code']);

        $data->listFields['creditQuantity'] = "Количество";
        $data->listFields['creditAmount'] = "Сума";
    }

    
    /**
     * След подготовката на показването на информацията
     */
    public static function on_AfterPrepareEmbeddedData($mvc, &$res)
    {
    	// Подготвяме страницирането
    	$data = $res;
    	 
    	foreach ($data->recs as $id => $rec) {
    		if (!isset($rec->creditQuantity) || !isset($rec->creditAmount)){
    			unset($data->recs[$id]);
    		}
    	}
    	 
    	$pager = cls::get('core_Pager',  array('itemsPerPage' => $mvc->listItemsPerPage));
        $pager->setPageVar($mvc->EmbedderRec->className, $mvc->EmbedderRec->that);
        $pager->addToUrl = array('#' => $mvc->EmbedderRec->instance->getHandle($mvc->EmbedderRec->that));
    	 
    	$pager->itemsCount = count($data->recs, COUNT_RECURSIVE);
    	$data->pager = $pager;
    
    	$start = $data->pager->rangeStart;
    	$end = $data->pager->rangeEnd - 1;
    
    	$data->summary = new stdClass();
    
    	if(count($data->recs)){
    		$count = 0;
    		foreach ($data->recs as $id => $rec){
    
    			// Показваме само тези редове, които са в диапазона на страницата
    			if($count >= $start && $count <= $end){
	    			//$rec->id = $count + 1;
	    			$row = $mvc->getVerbalDetail($rec);
	    			$data->rows[$id] = $row;
    			}
    			// Сумираме всички суми и к-ва
    			foreach (array('baseQuantity', 'baseAmount', 'debitAmount', 'debitQuantity', 'creditAmount', 'creditQuantity', 'blAmount', 'blQuantity') as $fld){
    				if(!is_null($rec->$fld)){
    					$data->summary->$fld += $rec->$fld;
    				}
    			}
    			$count++;
    		}
    	}
    
    	$Double = cls::get('type_Double');
    	$Double->params['decimals'] = 2;
    
    	foreach ((array)$data->summary as $name => $num){
    		$data->summary->$name  = $Double->toVerbal($num);
    		if($num < 0){
    			$data->summary->$name  = "<span class='red'>{$data->summary->$name}</span>";
    		}
    	}
    
    	$mvc->recToVerbal($data);
    
    	$res = $data;
    }
    
    
    /**
     * Вербалното представяне на записа
     */
    private function recToVerbal($data)
    {
    	$data->row = new stdClass();

    	foreach (range(1, 3) as $i){
    		if(!empty($data->rec->{"ent{$i}Id"})){
    			$data->row->{"ent{$i}Id"} = "<b>" . acc_Lists::getVerbal($data->accInfo->groups[$i]->rec, 'name') . "</b>: ";
    			$data->row->{"ent{$i}Id"} .= acc_Items::fetchField($data->rec->{"ent{$i}Id"}, 'titleLink');
    		}
    	}
    
    	if(!empty($data->rec->action)){
    		$data->row->action = ($data->rec->action == 'filter') ? tr('Филтриране по') : tr('Групиране по');
    		$data->row->groupBy = '';
    		 
    		$Varchar = cls::get('type_Varchar');
    		foreach (range(1, 3) as $i){
    			if(!empty($data->rec->{"grouping{$i}"})){
    				$data->row->groupBy .= acc_Items::getVerbal($data->rec->{"grouping{$i}"}, 'title') . ", ";
    			} elseif(!empty($data->rec->{"feat{$i}"})){
    				$data->rec->{"feat{$i}"} = ($data->rec->{"feat{$i}"} == '*') ? $data->accInfo->groups[$i]->rec->name : $data->rec->{"feat{$i}"};
    				$data->row->groupBy .= $Varchar->toVerbal($data->rec->{"feat{$i}"}) . ", ";
    			}
    		}
    		 
    		$data->row->groupBy = trim($data->row->groupBy, ', ');
    		 
    		if($data->row->groupBy === ''){
    			unset($data->row->action);
    		}
    	}
    }
    

    /**
     * Рендира вградения обект
     *
     * @param stdClass $data
     */
    public function renderEmbeddedData(&$embedderTpl, $data)
    {
		$tpl = $this->getReportLayout();

		$explodeTitle = explode(" » ", $this->title);
		
		$title = tr("|{$explodeTitle[1]}|*");
        $tpl->replace($title, 'TITLE');
        $this->prependStaticForm($tpl, 'FORM');

        $tpl->placeObject($data->row);

        $tableMvc = new core_Mvc;

        $tableMvc->FLD('creditAmount', 'int', 'tdClass=accCell');


        $table = cls::get('core_TableView', array('mvc' => $tableMvc));

        $tpl->append($table->get($data->rows, $data->listFields), 'DETAILS');

        $data->summary->colspan = count($data->listFields);

        if($data->bShowQuantities ){
            $data->summary->colspan -= 4;
            if($data->summary->colspan != 0 && count($data->rows)){
                $beforeRow = new core_ET("<tr style = 'background-color: #eee'><td colspan=[#colspan#]><b>" . tr('ОБЩО') . "</b></td><td style='text-align:right'><b>[#creditAmount#]</b></td></tr>");
            }
        }

        if($beforeRow){
            $beforeRow->placeObject($data->summary);
            $tpl->append($beforeRow, 'ROW_BEFORE');
        }
        
        if($data->pager){
        	$tpl->append($data->pager->getHtml(), 'PAGER_BOTTOM');
        	$tpl->append($data->pager->getHtml(), 'PAGER_TOP');
        }

        $embedderTpl->append($tpl, 'data');
    }


    /**
     * Скрива полетата, които потребител с ниски права не може да вижда
     *
     * @param stdClass $data
     */
    public function hidePriceFields()
    {
        $innerState = &$this->innerState;

        unset($innerState->recs);
    }


    /**
     * Коя е най-ранната дата на която може да се активира документа
     */
    public function getEarlyActivation()
    {
        $activateOn = "{$this->innerForm->to} 23:59:59";

        return $activateOn;
    }


    /**
     * Ще се експортирват полетата, които се
     * показват в табличния изглед
     *
     * @return array
     */
    public function getExportFields ()
    {

        $exportFields['ent1Id']  = "Контрагенти";
        $exportFields['creditAmount']  = "Кредит";

        return $exportFields;
    }


}