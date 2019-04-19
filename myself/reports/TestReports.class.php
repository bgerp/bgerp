 <?php


/**
 * Мениджър на тестови отчети
 *
 * @category  bgerp
 * @package   myself
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Тестовe » Тестов отчет
 */
class myself_reports_TestReports extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,acc,sales,purchase,debug';
    
    
    /**
     * Брой записи на страница
     *
     * @var int
     */
    protected $listItemsPerPage = 30;
    
    
    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField = 'genericId';
    
    
    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields = 'contragent,checkDate,crmGroup,typeOfInvoice,unpaid';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        //Период
        $fieldset->FLD('prognose', 'set(yes = )',  'caption=Прогноза,after=title,refreshForm,silent,single=none');
        
        $fieldset->FLD('period', 'key(mvc=acc_Periods,title=title)', 'caption = За месец,after=prognose,single=none');
        
        $fieldset->FLD('from', 'date', 'caption=Период->От,after=period,single=none');
        $fieldset->FLD('duration','time(suggestions=1 седмица| 1 месец| 2 месеца| 3 месеца| 6 месеца| 12 месеца)', 'caption=Период->Продължителност,after=from,single=none');
       
        $fieldset->FLD('group', 'treelist(mvc=cat_Groups,select=name, parentId=parentId)', 'caption=Артикули->Група артикули,after=duration,single=none');
        
        
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
     *                                      $Driver
     * @param embed_Manager       $Embedder
     * @param stdClass            $data
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;
        
        if ($rec->prognose != 'yes') {
            
            $form->setField('period', 'input=none');
        }else{
             $form->setField('from', 'input=none');
            $form->setField('duration', 'input=none');
           
        }
        
        $dat=dt::today()-1;
       
        $form->setDefault('from',date("01.01.$dat"));
        
        $form->setDefault('duration', '1 год.');
       
        
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager     $Embedder
     * @param core_Form         $form
     * @param stdClass          $data
     */
    protected static function on_AfterInputEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$form)
    {
        $rec = $form->rec;
        
        if ($form->isSubmitted()) {
           
        }
    }
    
    
    /**
     * Кои записи ще се показват в таблицата
     *
     * @param stdClass $rec
     * @param stdClass $data
     *
     * @return array
     */
    protected function prepareRecs($rec, &$data = null)
    {
        $recs = array();
       
        //Ако е избрано "Прогноза"
        if($rec->prognose == 'yes' && isset($rec->period)){
            
            $firstDayOfMonth = (acc_Periods::fetch($rec->period)->start);
            
            $startDate = dt::addMonths(-13,$firstDayOfMonth,false);
           
            $endDate = dt::addDays(-1,dt::addMonths(3,$startDate),false);
            
            //Масив с коефициенти
           // $today = date_format(new DateTime('10.04.2017'), 'Y-m-d');
            $today = dt::today();
            
            $beginDate = dt::addDays(1,dt::getLastDayOfMonth(dt::addMonths(-4,$today)),false);
            $lastDate = dt::addDays(-1,dt::addMonths(3,$beginDate),false);
            
            $beginDateYear = dt::addMonths(-12,$beginDate);
            $lastDateYear = dt::addMonths(-12,$lastDate);
            
            $NowArrForCoef = self::getInputArticuls($rec, $beginDate, $lastDate);
            $LastArrForCoef = self::getInputArticuls($rec, $beginDateYear, $lastDateYear);
         
            $coefficients = array();
            foreach ($LastArrForCoef as $key => $val){
                
                if (!in_array($key, array_keys($NowArrForCoef))) {
                    $coefficients[$key] = 0;
                    continue;
                }
                
                foreach ($NowArrForCoef as $k => $v){
                    
                    if (!in_array($k, array_keys($LastArrForCoef))) {
                        $coefficients[$key] = 1 ;
                        continue;
                    }
            
                    if ($key == $k) { 
                        $coefficients[$key] = $v->quantity/$val->quantity; 
                    }
                   
                }
                
            }
            
        }else{
            
            $startDate = $rec->from;
            $endDate = dt::addSecs($rec->duration, $rec->from,false);
        }
      
        $rec->from = $startDate;
        $rec->to = $endDate;
        
        $allInProd = self::getInputArticuls($rec,$startDate,$endDate);
        
        if($rec->prognose == 'yes' && isset($rec->period)){
       
            foreach ($allInProd as $key => $val){
            
                $val->quantity = $val->quantity/3;
                
                if (!in_array($key, array_keys($coefficients))) {
                       $allInProd[$key]->coefficient = 0;
                       continue;
                }
                
                foreach ($coefficients as $prId => $coef){
                    
                    if ($key == $prId) {
                        $allInProd[$key]->coefficient = $coef;
                    }
                  
                }
            }
        }
      
        //Генерично заменяеми артикули
        $queryS = planning_ObjectResources::getQuery();
        
        while ($generics = $queryS->fetch()) {
            
            if (!is_array($genericProducts[$generics->likeProductId])){
                
            $genericProducts[$generics->likeProductId] = array($generics->objectId);
            
            }else{
                array_push($genericProducts[$generics->likeProductId] , $generics->objectId);
            }
        }
        
        //Всички влагани през периода артикули
        $prodIds = arr::extractValuesFromArray($allInProd, 'productId');
       
        $genericProd = $genericQuantity = array();
        
        foreach ($genericProducts as $key => $val){
              
            $result = array_intersect($prodIds, $val);  //Влагни артикули, който са от групата на генеричния
            
            
            foreach ($result as $k=>$v){
                
                //Масив с общите количества на генеричните артикули (сумата от количествата на съставните артикули)
                $genericQuantity[$key] += $allInProd[$v]->quantity;
                
                //Артикул, който е част от генеричен
                $genericProd[$v]=(object)array(
                    'productId' => $v,
                    'measure' => $allInProd[$v]->measure,
                    'quantity' => $allInProd[$v]->quantity,
                    'coefficient' => $allInProd[$v]->coefficient,
                    'genericId' => $key,
                );
                
            }
            
        }
       
              $genProdIds = arr::extractValuesFromArray($genericProd, 'productId');
            
              //Изключваме от общия масив онези артикули, които са част от генеричен артикул
              foreach ($allInProd as $key => $val){
                  
                  if (in_array($val->productId, $genProdIds)){
                      
                      unset($allInProd[$key]);
                      
                  }
              }
               
              // Включваме артикулите, които са съставни на генеричните в общия масив
              foreach ($genericProd as $key =>$val){
                  
                  $val->generucQuantity = $genericQuantity[$val->genericId]; 
                 
                  array_unshift($allInProd, $val);
                   
              }
             
             $recs = $allInProd;
        
        return $recs;
    }
    
    
    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec
     *                         - записа
     * @param bool     $export
     *                         - таблицата за експорт ли е
     *
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = false)
    {
        $fld = cls::get('core_FieldSet');
        
        if ($export === false) {
            
            $fld->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
            
            $fld->FLD('measure', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,tdClass=centered');
            
            $fld->FLD('quantity', 'varchar', "caption=Вложено");
            
            if($rec->prognose == 'yes'){
                
                $fld->FLD('coefficient', 'varchar', "caption=Коефициент");
                $fld->FLD('prognose', 'varchar', "caption=Прогноза");
                
            }
        } else {
           
        }
        
        return $fld;
    }
    
    
    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec
     *                       - записа
     * @param stdClass $dRec
     *                       - чистия запис
     *
     * @return stdClass $row - вербалния запис
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {
        $isPlain = Mode::is('text', 'plain');
        $Int = cls::get('type_Int');
        $Date = cls::get('type_Date');
        
        $row = new stdClass();
    
        if (isset($dRec->genericId)) {
            $row->genericId = 'Генеричен артикул: '.cat_Products::getHyperlink($dRec->genericId).
                "<span class= 'fright'><span class= ''>" .
                ' Общо: '.
                core_Type::getByName('double(decimals=2)')->toVerbal($dRec->generucQuantity).' '.
                cat_UoM::fetchField($dRec->measure, 'shortName')."</span>";
        }else{
            
            $row->genericId = 'Незаменяеми артикули ';
            
        }
        
        if (isset($dRec->productId)) {
            $row->productId = cat_Products::getLinkToSingle_($dRec->productId, 'name');
        }
        if (isset($dRec->measure)) {
            $row->measure = cat_UoM::fetchField($dRec->measure, 'shortName');
        }
        
        $row->quantity = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->quantity);
        
        $row->coefficient = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->coefficient);
        
        $row->prognose = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->coefficient * $dRec->quantity);
        
        return $row;
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager     $Embedder
     * @param core_ET           $tpl
     * @param stdClass          $data
     */
    protected static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
    {
      
    }
    
    
    
    /**
     * След подготовка на реда за експорт
     *
     * @param frame2_driver_Proto $Driver
     * @param stdClass            $res
     * @param stdClass            $rec
     * @param stdClass            $dRec
     */
    protected static function on_AfterGetExportRec(frame2_driver_Proto $Driver, &$res, $rec, $dRec, $ExportClass)
    {
      
    }
    
    /*
     * Връща масив с всички вложени артикули,
     * и вложените количества в производството при зададен период
     *
     * @param date            $startDate начало  на периода
     * @param date            $endDate край  на периода
     *
     * @return array          Масив артикули и количества
     */
    public static function getInputArticuls($rec,$startDate,$endDate)
    {
        $detailsArr = array('planning_DirectProductionNote'=>'planning_DirectProductNoteDetails',
                            'planning_ConsumptionNotes'=>'planning_ConsumptionNoteDetails',
                            'planning_ReturnNotes'=>'planning_ReturnNoteDetails'
                           );
        
        $allInProd = array();
        
        foreach ($detailsArr as $master => $details){
            
            $plQuery = $details::getQuery();
            
            $plQuery->EXT('valior', "$master", 'externalName=valior,externalKey=noteId');
            
            $plQuery->EXT('state', 'cat_Products', 'externalName=state,externalKey=productId');
            
            $plQuery->where("#state != 'rejected'");
            
            $plQuery->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
            
            $plQuery->EXT('canBuy', 'cat_Products', 'externalName=canBuy,externalKey=productId');
            
            $plQuery->EXT('groups', 'cat_Products', 'externalName=groups,externalKey=productId');
            
            //Филтър по период
            $plQuery->where("#valior >= '{$startDate}' AND #valior <= '{$endDate}'");
            
            //Филтър по групи артикули
            if (isset($rec->group)) {
                $plQuery->likeKeylist('groups', $rec->group);
            }
            
            $plQuery->where("#canStore = 'yes' AND #canBuy = 'yes'");
            
            while ($prodRec = $plQuery->fetch()){
                
                $quantity = 0;
                
                $id = $prodRec->productId;
                
                if($master instanceof planning_ReturnNotes ){
                    $quantity = -1*$prodRec->quantity;
                }else{
                    $quantity = $prodRec->quantity;
                }
                
                // Запис в масива
                if (!array_key_exists($id, $allInProd)) {
                    $allInProd[$id] = (object) array(
                        
                        
                        'productId' => $prodRec->productId,                           //Id на артикула
                        'measure' => $prodRec->measureId,                             //Мярка
                        'quantity' => $quantity,                                      //Текущ период - количество
                        
                    );
                } else {
                    $obj = &$allInProd[$id];
                    
                    $obj->quantity += $quantity;
                    
                }
                
            }
        }
        
        return $allInProd;
        
    }
    
}