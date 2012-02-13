<?php



/**
 * Себестойности на продуктите от каталога
 *
 *
 * @category  bgerp
 * @package   catpr
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Себестойност
 */
class catpr_Costs extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Себестойност';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools,
                     catpr_Wrapper, plg_AlignDecimals, plg_SaveAndNew,
                     plg_LastUsedKeys';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'productId, xValiorDate, xValiorTime, publicPrice, baseDiscount, cost, tools=Пулт';
    
    
    /**
     * Списък от полета, които са външни ключове към други модели
     *
     * @see plg_LastUsedKeys
     *
     * @var string
     */
    var $lastUsedKeys = 'priceGroupId';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Кой може да го прочете?
     */
    var $canRead = 'admin,user';
    
    
    /**
     * Кой може да го промени?
     */
    var $canEdit = 'admin,catpr';
    
    
    /**
     * Кой може да добавя?
     */
    var $canAdd = 'admin,catpr,broker';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'admin,catpr,broker';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin,catpr,broker';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'admin,catpr';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('productId', 'key(mvc=cat_Products,select=name,allowEmpty)', 'mandatory,input,caption=Продукт,remember=info');
        $this->FLD('priceGroupId', 'key(mvc=catpr_Pricegroups,select=name,allowEmpty)', 'mandatory,input,caption=Група');
        $this->FLD('valior', 'datetime', 'input,caption=Вальор');
        $this->FLD('cost', 'double(minDecimals=2)', 'mandatory,input,caption=Себестойност');
        
        $this->EXT('baseDiscount', 'catpr_Pricegroups', 'externalKey=priceGroupId,input=none,caption=Максимум->Отстъпка');
        
        $this->FNC('publicPrice', 'double(decimals=2,minDecimals=2)', 'caption=Максимум->Цена');
        
        // Полета, използвани за форматиране на вальора
        $this->XPR('xValiorDate', 'varchar', 'DATE(#valior)', 'caption=Вальор->Дата');
        $this->XPR('xValiorTime', 'varchar', 'TIME(#valior)', 'caption=Вальор->Час');
        
        // Кода в този модел гарантира, че ако вальора е бъдеща дата, то часа му е нула. Предвид 
        // това, този уникален индекс гарантира, че не могат да се въведат две себестойности за 
        // един продукт към една и съща *бъдеща* дата.
        $this->setDbUnique('productId, valior');
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function on_CalcPublicPrice($mvc, &$rec)
    {
        $rec->publicPrice = self::getPublicPrice($rec);
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, необходимо за това действие
     */
    function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        switch ($action) {
            case 'edit' :
                // Не могат да се променят записи за себестойност
                $requiredRoles = 'no_one';
                break;
            case 'delete' :
                // Могат да се изтриват само себестойности към бъдещи дати
                if ($rec && $rec->xValiorDate <= dt::today()) {
                    $requiredRoles = 'no_one';
                }
                break;
        }
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    function on_AfterPrepareEditForm($mvc, $data)
    {
        $form = $data->form;
        $rec = $form->rec;
        
        // Скриваме истинското поле за вальор от формата и добавяме фиктивно поле от тип `date`
        // (а не `datetime`). Целта е потребителя да може да въвежда само дати (без час), а 
        // системата автоматично да изчислява и записва часа, на базата на правила:
        //  * за бъдещи дати   - часа е нула (00:00:00)
        //  * за текущата дата - часа е текущия час
        //  * за минали дати   - не могат да се въвеждат, забранено е.
        $form->setField('valior', 'input=none');
        $form->FNC('fValior', 'date', 'mandatory,input,caption=Вальор,remember');
        $form->FNC('fIsChange', 'int', 'input=hidden');
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    function on_AfterInputEditForm($mvc, $form)
    {
        if (!$form->isSubmitted()) {
            if ($baseId = Request::get('baseId', 'key(mvc=' . $this->className . ')')) {
                $form->rec = $mvc->fetch($baseId);
                $form->setDefault('fIsChange', 1);
                unset($form->rec->id);
            }
            $form->setDefault('fValior', dt::addDays(1, dt::today()));
            
            return;
        }
        
        $today = dt::today();
        
        switch (true) {
            case ($today > $form->rec->fValior) :
            // Себестойност към дата в миналото - недопустимо!
            $form->setError('fValior',
                'Не се допуска промяна на себестойност със задна дата.');
            break;
            case ($today < $form->rec->fValior) :
            // Себестойност към дата в бъдещето - "забиваме" часа на 00:00:00
            $form->rec->valior = $form->rec->fValior . ' ' . '00:00:00';
            break;
            case ($today == $form->rec->fValior) :
            default :
            // Себестойност към днешна дата - "забиваме" часа на текущия час
            $form->rec->valior = $form->rec->fValior . ' ' . date('H:i:s');
            
            if ($form->rec->fIsChange) {
                $form->setWarning('fValior', 'Внимание, променяте себестойността с днешна дата!');
            }
        }
        
        if(!$this->isUnique($form->rec, $fields)) {
            if (in_array('valior', $fields)) {
                $fields[] = 'fValior';
            }
            $form->setError($fields, "Вече съществува запис със същите данни");
        }
    }
    
    
    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->listFilter->setField('productId',
            'placeholder=Всички Продукти,caption=Продукт,input,silent,mandatory=,remember');
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
        $data->listFilter->showFields = 'productId';
        $data->listFilter->input('productId', TRUE /*silent*/);
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
        $data->query->orderBy('productId');
        $data->query->orderBy('valior', 'desc');
        
        if ($productId = $data->listFilter->rec->productId) {
            // Показване само на един продукт
            $data->query->where("#productId = {$data->listFilter->rec->productId}");
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    function on_AfterPrepareListRows($mvc, $data)
    {
        $rows = &$data->rows;
        $recs = &$data->recs;
        
        $prevProductId = NULL;
        $prevGroupId = NULL;
        
        // Ако има филтър по продукт, показваме само него, но заедно с историята на 
        // себестойностите му. В противен случай показваме само актуалната и бъдещите цени на
        // продуктите.
        $bHideHistory = empty($data->listFilter->rec->productId);
        
        if(count($data->rows)) {
            foreach ($data->rows as $i=>&$row) {
                // Скриване на продукта и групата, ако са същите като в предходния ред.
                $rec = $recs[$i];
                
                if ($rec->productId == $prevProductId) {
                    $row->productId = '';
                    
                    if ($rec->priceGroupId != $prevGroupId) {
                        $row->ROW_ATTR['class'] .= ' pricegroup';
                    }
                    $row->ROW_ATTR['class'] .= ' quiet';
                    
                    if ($bHideHistory) {
                        unset($data->rows[$i]);
                        continue;
                    }
                }
                
                if ($rec->xValiorDate <= dt::today()) {
                    $prevProductId = $rec->productId;
                    $prevGroupId = $rec->priceGroupId;
                } else {
                    $row->ROW_ATTR['class'] .= ' quiet';
                    $row->ROW_ATTR['class'] .= ' future';
                    $row->ROW_ATTR['class'] .= ' pricegroup';
                }
                
                $isCurrentCost = empty($row->ROW_ATTR['class']) ||
                (strpos($row->ROW_ATTR['class'], 'quiet') === FALSE);
                
                if ($isCurrentCost) {
                    $row->ROW_ATTR['class'] .= ' current';
                    $prevGroupId = NULL;
                    
                    // Линк за "редактиране" на текущата себестойност. Тъй като себестойностите
                    // не могат да се променят в буквален смисъл, линкът е към екшъна за добавяне
                    // на нова себестойност, която да отмени текущата.
                    $editImg = "<img src=" . sbf('img/16/marketwatch.png') . ">";
                    
                    $editUrl = toUrl(
                        array(
                            $mvc,
                            'add',
                            'baseId' => $rec->id,
                            'ret_url' => TRUE
                        )
                    );
                    
                    if (!is_a($row->tools, 'core_ET')) {
                        $row->tools = new ET($row->tools);
                    }
                    
                    $row->tools->append(''
                        . '<div class="rowtools">'
                        . ht::createLink($editImg, $editUrl)
                        . '</div>'
                    );
                }
                
                // Форматиране на вальора - не показва часа, ако той е '00:00:00'
                if ($rec->xValiorTime == '00:00:00') {
                    $row->xValiorTime = '';
                }
                
                /*
                 *  Композиране на колоната макс.отстъпка
                 */
                $baseDiscount = new ET('<table><tr><td style="white-space: nowrap;">[#DISCOUNT#]</td><td style="white-space: wrap; text-align: right; width: 100%;">([#GROUP#])</td></tr></table>');
                
                //  Понеже `priceGroupId` не е в `$listFields`, фреймуърка не изчислява 
                // `$row->priceGroupId` се налага да го направим ръчно.
                //
                $row->priceGroupId = $mvc->getVerbal($rec, 'priceGroupId');   // ръчно!
                $baseDiscount->replace($row->priceGroupId, 'GROUP');
                $baseDiscount->replace($row->baseDiscount, 'DISCOUNT');
                
                $row->baseDiscount = $baseDiscount;
                
                /*
                 * Композиране на хипервръзка към продукт
                 */
                $row->productId = Ht::createLink($row->productId,
                    array($mvc, 'list', 'productId'=>$rec->productId)
                );
                
                if ($isCurrentCost && $bHideHistory) {
                
                }
            }
            
            if ($bHideHistory) {
                $mvc->processBulkForm($data);
            }
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function processBulkForm($data)
    {
        $this->prepareBulkForm($data);
        $this->inputBulkForm($data);
        $this->renderBulkForm($data);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function prepareBulkForm($data)
    {
        // Създаване на bulkForm
        /** @var core_Form $bulkForm */
        $bulkForm = &cls::get('core_Form');
        
        $rows = &$data->rows;
        $recs = &$data->recs;
        
        foreach ($rows as $i=>&$row) {
            $rec = &$recs[$i];
            
            if (strpos($row->ROW_ATTR['class'], 'current') !== FALSE) {
                $bulkForm->FLD("cost_{$rec->id}", 'double',
                    array(
                        'attr' => array(
                            'size' => 9,
                            'class' => 'inplace',
                        )
                    )
                );
                $bulkForm->setDefault("cost_{$rec->id}", $rec->cost);
            }
        }
        
        $bulkForm->FLD("valior", 'date',
            array(
                'mandatory' => 'mandatory',
                'attr'=>array(
                    'size' => 9,
                    'class' => 'inplace'
                )
            )
        );
        
        $bulkForm->action = array($this, 'list');
        
        $data->bulkForm = $bulkForm;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function inputBulkForm($data)
    {
        if (!$data->bulkForm) {
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            return;
        }
        
        $data->bulkForm->input();
        
        if ($data->bulkForm->isSubmitted()) {
            // Валидация на bulkForm
            
            $bulkRec = $data->bulkForm->rec;
            $today = dt::today();
            $valior = $data->bulkForm->rec->valior;
            
            if ($today >= $valior) {
                // Себестойност към дата в миналото - недопустимо!
                $data->bulkForm->setError('valior', 'Не се допуска промяна на себестойност със задна дата.');
            }
            
            if (!$data->bulkForm->gotErrors()) {
                // Екшън. Формата е валидирана - следват записи
                
                foreach ($data->recs as $oldRec) {
                    if ($data->bulkForm->fields["cost_{$oldRec->id}"]) {
                        $rec = clone($oldRec);
                        $rec->valior = $valior;
                        $rec->cost = $data->bulkForm->rec->{"cost_{$oldRec->id}"};
                        
                        if ($rec->cost != $oldRec->cost) {
                            unset($rec->id);
                            $this->save($rec);
                        }
                    }
                }
                
                redirect(array($this, 'list'));
            }
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function renderBulkForm($data)
    {
        if (!$data->bulkForm) {
            return;
        }
        
        $rows = &$data->rows;
        $recs = &$data->recs;
        
        if (count($rows)) {
            foreach ($rows as $i=>&$row) {
                $rec = &$recs[$i];
                
                if (strpos($row->ROW_ATTR['class'], 'current') !== FALSE) {
                    $row->cost = $data->bulkForm->renderInput("cost_{$rec->id}");
                }
            }
        }
        
        $rows[] = (object)array(
            'tools' => core_Html::createSbBtn('Запис', 'default', NULL, NULL, array('class' =>  'btn-save')),
            'baseDiscount' => 'Вальор',
            'cost' => $data->bulkForm->renderInput('valior')
        );
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function processBulkUpdate($data)
    {
        $rows = &$data->rows;
        $recs = &$data->recs;
        
        if (!count($rows)) {
            return;
        }
        
        /* @var $TypeDouble type_Double */
        $TypeDouble = cls::get('type_Double');
        
        // Ако сервираме HTTP POST заявка, значи имаме групова промяна на себестойности.
        $isPost = ($_SERVER['REQUEST_METHOD'] == 'POST');
        
        $today = dt::today();
        
        if ($isPost) {
            // Да валидираме вальора.
            $valior = Request::get('valior', 'date');
            
            if (!$valior) {
                $data->bulkErrors = 'Въведете вальор';
            } elseif ($today > $valior) {
                // Себестойност към дата в миналото - недопустимо!
                $data->bulkErrors = 'Не се допуска промяна на себестойност със задна дата.';
            }
        }
        
        //                $row->cost = $TypeDouble->renderInput("cost_{$rec->id}", $rec->cost,
        //                    array(
        //                        'class' => 'inplace number',
        //                        'size'  => 9
        //                    )
        //                );
        
        if ($isPost && empty($data->bulkErrors)) {
            redirect($this, 'list');
        }
        
        /* @var $Date type_Date */
        $Date = cls::get('type_Date');
        
        $rows[] = (object)array(
            'tools' => core_Html::createSbBtn('Запис'),
            'baseDiscount' => 'Вальор',
            'cost' => $Date->renderInput_('valior', null, array('class'=>'inplace date', 'size'=>9))
        );
        
        return empty($data->bulkErrors);
    }
    
    
    /**
     * Добавя след таблицата
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    function on_AfterRenderListTable($mvc, &$tpl, $data)
    {
        if (!$data->bulkForm) {
            return;
        }
        
        $formLayout = $data->bulkForm->layout ? new ET($data->bulkForm->layout) : $data->bulkForm->renderLayout();
        
        $views = array(
            'TITLE',
            'ERROR',
            'INFO',
            //            'FIELDS',
            'HIDDEN',
            'TOOLBAR',
            'METHOD',
            'ACTION'
        );
        
        foreach ($views as $view) {
            $method = 'render' . $view;
            $formLayout->append($data->bulkForm->$method(), "FORM_{$view}");
        }
        
        $formLayout->replace(false, "FORM_TOOLBAR");
        $formLayout->append($tpl, "FORM_FIELDS");
        
        $tpl = $formLayout->getContent();
    }
    
    
    /**
     * @todo Чака за документация...
     */
    private function bulkUpdate()
    {
        $valior = Request::get('valior', 'date');
        $costs = Request::get('cost');
        
        foreach ($costs as $id=>$cost) {
            $rec = (object)compact('id', 'valior', 'cost');
            
            /*
             * Валидация на $rec - същата като в on_AfterPrepareEditForm()  
             */
            
            $this->save($rec);
        }
        
        $result = new core_Redirect(array($this, 'list'));
        
        return $result;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function getPublicPrice($rec)
    {
        return (double)$rec->cost / (1 - (double)$rec->baseDiscount);
    }
    
    
    /**
     * Себестойността на продукт към дата или историята на себестойностите на продукта.
     *
     * @param int $id key(mvc=cat_Product)
     * @param string $date дата, към която да се изчисли себестойността или NULL за историята на
     * себестойностите.
     * @return array масив от записи на този модел - catpr_Costs
     */
    static function getProductCosts($id, $date = NULL)
    {
        $query = self::getQuery();
        
        $query->orderBy('valior', 'desc');
        $query->where("#productId = {$id}");
        
        if (isset($date)) {
            // Търсим себестойност към фиксирана дата. Това е най-новата себестойност с вальор 
            // преди тази дата.
            // В случай, че в датата има зададен час, търси се себестойността точно към този 
            // час. Иначе се търси себестойността към края на деня.
            $query->where(
                "DATE(#valior) < DATE('{$date}')"
                . ' OR '
                . '('
                . "DATE(#valior) = DATE('{$date}')"
                . ' AND '
                . "TIME(#valior) <= IF( TIME(TIMESTAMP('{$date}')), TIME(TIMESTAMP('{$date}')), '23:59:25' )"
                . ')'
            );
            $query->limit(1);
        }
        
        $result = array();
        
        while ($rec = $query->fetch()) {
            $result[] = $rec;
        }
        
        return $result;
    }
}
