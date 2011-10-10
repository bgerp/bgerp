<?php
/**
 * 
 * Детайл на модела @link catpr_Discounts
 * 
 * Всеки запис от модела съдържа конкретен процент отстъпка за конкретна ценова група 
 * (@see catpr_Pricegroups) към дата.
 * 
 * @category   BGERP
 * @package    catpr
 * @author     Stefan Stefanov <stefan.bg@gmail.com>
 * @title      Отстъпки-детайли
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 *
 */
class catpr_Discounts_Details extends core_Detail
{
	var $title = 'Отстъпки';
	
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools,
                     catpr_Wrapper, plg_Sorting, plg_SaveAndNew';
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     *
     * @var string
     */
    var $masterKey = 'discountId';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'discountId, priceGroupId, valior, discount, tools=Пулт';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Права
     */
    var $canRead = 'admin,user';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canEdit = 'admin,catpr';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canAdd = 'admin,catpr,broker';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canView = 'admin,catpr,broker';
    
    var $canList = 'admin,catpr,broker';
    
    /**
     *  @todo Чака за документация...
     */
    var $canDelete = 'admin,catpr';
	
    
    function description()
	{
		$this->FLD('discountId', 'key(mvc=catpr_Discounts,select=name)', 'mandatory,input,caption=Пакет,remember');
		$this->FLD('priceGroupId', 'key(mvc=catpr_Pricegroups,select=name)', 'mandatory,input,caption=Група,remember');
		$this->FLD('valior', 'date', 'input,caption=Вальор,mandatory,remember');
		
		// процент на отстъпка от публичните цени
		$this->FLD('discount', 'percent', 'mandatory,input,caption=Отстъпка');
	}


	/**
	 * Преди извличане на записите от БД
	 *
	 * @param core_Mvc $mvc
	 * @param StdClass $res
	 * @param StdClass $data
	 */
	function on_BeforePrepareListRecs($mvc, &$res, $data)
	{
		$data->query->orderBy('discountId');
		$data->query->orderBy('priceGroupId');
		$data->query->orderBy('valior', 'desc');

        if ($data->listFilter->rec->discountId) {
        	$data->query->where("#discountId = {$data->listFilter->rec->discountId}");
        }
	}
    

	function on_AfterPrepareListFilter($mvc, $data)
    {
 		$data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай');
        $data->listFilter->showFields = 'discountId';

        $data->listFilter->input('discountId', 'silent');
        
        if (!$data->listFilter->rec->discountId && is_null(Request::get('discountId'))) {
        	$data->listFilter->rec->discountId = Mode::get('catpr_Discounts_Details::listFilter::discountId');
        } else {
        	Mode::setPermanent('catpr_Discounts_Details::listFilter::discountId', $data->listFilter->rec->discountId);
        }
    }
	
    
	function on_AfterPrepareListRecs($mvc, $data)
	{
		if (!count($data->rows)) {
			return;
		}
		$rows = &$data->rows;
		$recs = &$data->recs;
		
		$prevDiscountId = NULL;
		$prevGroupId    = NULL;
		
		foreach ($data->rows as $i=>&$row) {
			$rec = $recs[$i];
			if ($rec->discountId == $prevDiscountId) {
				$row->discountId = '';
				if ($rec->priceGroupId == $prevGroupId) {
					$row->CSS_CLASS[] = 'quiet';
				}
			} else {
				$row->discountId = "<strong>{$row->discountId}</strong>";
			}
			
			$prevDiscountId = $rec->discountId;
			$prevGroupId    = $rec->priceGroupId;
		}
	}
	
	
	function on_AfterPrepareListToolbar($mvc, $data)
	{
		$data->toolbar->addBtn(
			'Нова Отстъпка', 
			array(
				$this, 
				'add', 
				'discountId'=>$data->listFilter->rec->discountId, 
				'ret_url'=>TRUE
			), 
			array(
				'class'=>'btn-add', 
				'id'=>'btnAdd'
			)
		);
		$data->toolbar->addBtn(
			'Нов Пакет', 
			array(
				'catpr_Discounts', 'add', 'ret_url'=>TRUE
			),
			array(
				'class'=>'btn-add'
			)
		);
	}

	
	function on_AfterPrepareEditForm($mvc, $data)
	{
//		$data->form->setDefault('valior', dt::addDays(1, dt::today()));
	}
}