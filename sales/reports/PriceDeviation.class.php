<?php



/**
 * Мениджър на отчети за отклонения от цените
 *
 * @category  bgerp
 * @package   sales
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Продажби » Отклонения от цените
 */
class sales_reports_PriceDeviation extends frame2_driver_TableData
{

    const NUMBER_OF_ITEMS_TO_ADD = 50;
    
    const MAX_POST_ART = 10;
    
    
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,manager,store,planing,purchase,sales';
    
    
    /**
     * Брой записи на страница
     *
     * @var int
     */
    protected $listItemsPerPage = 30;
    
    
    /**
     * Полета от таблицата за скриване, ако са празни
     *
     * @var int
     */
    protected $filterEmptyListFields;
    
    
    /**
     * Полета за хеширане на таговете
     *
     * @see uiext_Labels
     * @var varchar
     */
    protected $hashField;
    
    
    /**
     * Кое поле от $data->recs да се следи, ако има нов във новата версия
     *
     * @var varchar
     */
    protected $newFieldToCheck = 'conditionQuantity';
    
    
    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField ='saleId';


    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields = 'typeOfQuantity,additional,storeId,groupId';



    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
    	$fieldset->FLD('from', 'date(smartTime)', 'caption=От,after=title,single=none1,mandatory');
    	$fieldset->FLD('to',    'date(smartTime)', 'caption=До,after=from,single=none1,mandatory');
        $fieldset->FLD('dealers', 'users(rolesForAll=ceo|rep_cat, rolesForTeams=ceo|manager|rep_acc|rep_cat)', 'caption=Дилъри,placeholder=Всички,after=to');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver $Driver
     * @param embed_Manager $Embedder
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;
        $rec->flag = TRUE;

    }

    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager $Embedder
     * @param core_Form $form
     * @param stdClass $data
     */
    protected static function on_AfterInputEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$form)
    {

     
        if ($form->isSubmitted()) {
   
        }
    }


    /**
     * Кои записи ще се показват в таблицата
     *
     * @param stdClass $rec
     * @param stdClass $data
     * @return array
     */
    protected function prepareRecs($rec, &$data = NULL)
    {
        $recs = array();
        $recsVar1 = array();
        
        		$dealersId = keylist::toArray($rec->dealers);
        	
                $query = sales_SalesDetails::getQuery();
                
                $query->EXT('contragentClassId', 'sales_Sales', 'externalName=contragentClassId,externalKey=saleId');
                
                $query->EXT('contragentId', 'sales_Sales', 'externalName=contragentId,externalKey=saleId');
                
                $query->EXT('dealerId', 'sales_Sales', 'externalName=dealerId,externalKey=saleId');
                
                $query->EXT('valior', 'sales_Sales', 'externalName=valior,externalKey=saleId');
                
                $query->EXT('containerId', 'sales_Sales', 'externalName=containerId,externalKey=saleId');
                
                $query->where(array("#valior>= '[#1#]' AND #valior <= '[#2#]'", $rec->from, $rec->to . ' 23:59:59'));
                
              
                while ($saleProducts = $query->fetch()) {
                	     
                	if ($rec->dealers && !in_array($saleProducts->dealerId, $dealersId))continue;
                
                	$productId =  $saleProducts->productId;
                	
                	if (cat_Products::fetch($productId)->isPublic == 'no')continue;
                	
                  	$saleProductsArr[]=$saleProducts;
                	
                	$selfPrice = price_ListRules::getPrice(price_ListRules::PRICE_LIST_COST, $productId, $packagingId, $datetime);
                	
                	$selfPriceArr[$saleProducts->saleId][$productId] = $selfPrice;
                	
                	//цена на артикула за клиента
                	$contragentFuturePrice =  cls::get('price_ListToCustomers')->getPriceInfo($saleProducts->contragentClassId, $saleProducts->contragentId, $productId,NULL,1000);

                	$isPublic = cat_Products::fetch($productId)->isPublic;
                	
                	// цена на артикула по каталог(за стандартни артикули)
                   	$productCatPrice = price_ListRules::getPrice(price_ListRules::PRICE_LIST_CATALOG, $productId, $packagingId, $datetime);
                	
                	$productCostPrice = price_ListRules::getPrice(price_ListRules::PRICE_LIST_COST, $productId, $packagingId, $datetime);
              
                   
                    
                 
                    
                    
                    
                    if (($saleProducts->price < $selfPrice) || (!empty($productCatPrice) && ($saleProducts->price > $productCatPrice))){
                    	
                    	$recs[$productId]=(object)array(
							                    			'saleId'=>$saleProducts->saleId,
							                    			'productId'=>$productId,
							                    			'measure' => cat_Products::fetchField($productId, 'measureId'),
							                    			'quantity' => $saleProducts->quantity,
							                    			'selfPrice'=>$selfPrice,
							                    			'catPrice'=>$productCatPrice,
							                    			'price'=>$saleProducts->price,
							                    			'containerId'=>$saleProducts->containerId
							                    			
							                    	);
                    	
                     }
                    

                       

                }
                $expQuery = sales_SalesDetails::getQuery();
 
       //  bp($recs);      

        return $recs;

    }


    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec   - записа
     * @param boolean $export - таблицата за експорт ли е
     * @return core_FieldSet  - полетата
     */
    protected function getTableFieldSet($rec, $export = FALSE)
    {
        $fld = cls::get('core_FieldSet');
       
      	if($export === FALSE){
      	
      		$fld->FLD('saleId', 'varchar', 'caption=Сделка');
      		$fld->FLD('productId', 'varchar', 'caption=Артикул');
      		$fld->FLD('deviation', 'varchar', 'caption=Отклонение,tdClass=centered');
      		$fld->FLD('measure', 'varchar', 'caption=Мярка,tdClass=centered');
      		$fld->FLD('quantity', 'double(smartRound,decimals=2)', 'caption=Количество,smartCenter');
      		$fld->FLD('price', 'double', 'caption=Прод. цена,smartCenter');
      		$fld->FLD('selfPrice', 'double', 'caption=Себестойност,smartCenter');
       		$fld->FLD('catPrice', 'double', 'caption=Каталожна цена,smartCenter');
      	} else {
      		$fld->FLD('saleId', 'varchar', 'caption=Сделка');
      		$fld->FLD('productId', 'varchar', 'caption=Артикул');
      		$fld->FLD('deviation', 'varchar', 'caption=Отклонение,tdClass=centered');
      		$fld->FLD('measure', 'varchar', 'caption=Мярка,tdClass=centered');
      		$fld->FLD('quantity', 'double(smartRound,decimals=2)', 'caption=Количество,smartCenter');
      		$fld->FLD('soldPrice', 'double', 'caption=Прод. цена,smartCenter');
      		$fld->FLD('selfPrice', 'double', 'caption=Себестойност,smartCenter');
       		$fld->FLD('catPrice', 'double', 'caption=Каталожна цена,smartCenter');
      	
        }
      
        return $fld;

    }


    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec  - записа
     * @param stdClass $dRec - чистия запис
     * @return stdClass $row - вербалния запис
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {

    	//bp($dRec);
        $Int = cls::get('type_Int');

        $row = new stdClass();
        
        
     	if ($dRec->selfPrice > $dRec->price){
        $marker =(double)(($dRec->price - $dRec->selfPrice)/$dRec->price)*100;
     	}
     	if($dRec->price > $dRec->catPrice){
     		$marker =-1*(double)(($dRec->catPrice - $dRec->price)/$dRec->catPrice)*100;
     	}
     		
        $row->deviation = core_Type::getByName('double(decimals=2)')->toVerbal($marker)."%";
     
        if(isset($dRec->saleId)) {
        	$row->saleId =  sales_Sales::getLinkToSingle($dRec->saleId);
        }

        if(isset($dRec->productId)) {
            $row->productId =  cat_Products::getShortHyperlink($dRec->productId);
        }

        if(isset($dRec->quantity)) {
            $row->quantity =  core_Type::getByName('double(decimals=2)')->toVerbal($dRec->quantity);
        }

        if(isset($dRec->price)) {
            $row->price = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->price);
         }
         
        if(isset($dRec->measure)) {
            $row->measure = cat_UoM::fetchField($dRec->measure,'shortName');
        }

        if(isset($dRec->selfPrice)) {
            $row->selfPrice = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->selfPrice);
        }
        
        if(isset($dRec->catPrice)) {
        	$row->catPrice =core_Type::getByName('double(decimals=2)')->toVerbal($dRec->catPrice);
        }
        return $row;
    }

}