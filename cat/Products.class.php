<?php

/**
 * Регистър на продуктите
 */
class cat_Products extends core_Master {

    /**
     * Интерфайси, поддържани от този мениджър
     */
    var $interfaces = 'acc_RegisterIntf,cat_ProductAccRegIntf';

    /**
     *  @todo Чака за документация...
     */
    var $title = "Продукти в каталога";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools, plg_SaveAndNew, acc_plg_Registry,
                     cat_Wrapper, plg_Sorting, plg_Printing, Groups=cat_Groups';
    
    
    var $details = 'cat_Products_Params';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $singleTitle = "";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'id,title,categoryId,groups, prices=Цени';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'id';
    
    
    /**
     * Права
     */
    var $canRead = 'admin,user';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canEdit = 'admin,acc';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canAdd = 'admin,acc,broker';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canView = 'admin,acc,broker';
    
    var $canList = 'admin,acc,broker';
    
    /**
     *  @todo Чака за документация...
     */
    var $canDelete = 'admin,acc';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $features = 'groups,unitId';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('code', 'int', 'caption=Код, mandatory,remember=info');
        $this->FLD('title', 'varchar(255)', 'caption=Име, mandatory,remember=info');
        $this->FLD('categoryId', 'key(mvc=cat_Categories,select=name)', 'caption=Категория, mandatory,remember=info');
        $this->FLD('unitId', 'key(mvc=common_Units, select=name)', 'caption=Мярка,mandatory,notSorting');
        $this->FLD('gln', 'gs1_TypeEan13', 'caption=GLN код');
        $this->FLD('inBrief', 'varchar(255)', 'caption=Кратко описание, notSorting');
        $this->FLD('description', 'text', 'caption=Детайли');
        $this->FLD('image1', 'fileman_FileType(bucket=productsImages)', 'caption=Снимка, notSorting');
        $this->FLD('image2', 'fileman_FileType(bucket=productsImages)', 'caption=Снимка 2');
        $this->FLD('image3', 'fileman_FileType(bucket=productsImages)', 'caption=Снимка 3');
        $this->FLD('image4', 'fileman_FileType(bucket=productsImages)', 'caption=Снимка 4');
        $this->FLD('image5', 'fileman_FileType(bucket=productsImages)', 'caption=Снимка 5');
        $this->FLD('groups', 'keylist(mvc=cat_Groups, select=title)', 'caption=Групи');
        
        $this->FNC('prices', 'varchar(255)');
        $this->FNC('group', 'key(mvc=cat_Groups)', 'caption=Група');
        
        $this->setDbUnique('code');
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function on_CalcGroup($mvc, &$rec) {
        $rec->group = (int)substr($rec->groups, 1); // ид-то на първата група от списъка
    }
    
    
    /**
     * Изпълнява се след подготовка на ЕдитФормата
     */
    function on_AfterPrepareEditForm($mvc, $data)
    {
        if(!$data->form->rec->id && ($code = Mode::get('catLastProductCode'))) {
            
            if(is_numeric($code)) {
                $code++;
                
                if(!$mvc->fetch("#code = $code")) {
                    $data->form->rec->code = $code;
                }
            }
        }
        
        if ($data->form->rec->id) {
        	cat_Products_Params::getParamsForm($data->form->rec->id, $data->form);
        }
    }
    
    
    /**
     * Изпълнява се след въвеждане на данните от Request
     */
    function on_AfterInputEditForm($mvc, $form)
    {
        if(!$form->rec->id && ($code = Request::get('code', 'int'))) {
            Mode::setPermanent('catLastProductCode', $code);
        }
    }
    
    
    /**
     * Създаваме кофа
     *
     * @param core_MVC $mvc
     * @param stdClass $res
     */
    function on_AfterSetupMvc($mvc, &$res)
    {
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $res .= $Bucket->createBucket('productsImages', 'Илюстрация на продукта', 'jpg,jpeg', '3MB', 'user', 'every_one');
    }
    
    
    /**
     * Добавяне в таблицата на линк към детайли на продукта. Обр. на данните
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal ($mvc, $row, $rec)
    {
        $this->Units = cls::get('common_Units');
        
        $row->prices = Ht::createLink('Покажи', array('cat_PriceLists', 'List', 'productId' => $rec->id));
        
        // fancybox ефект за картинките
        $Fancybox = cls::get('fancybox_Fancybox');
        
        $tArr = array(200, 150);
        $mArr = array(600, 450);
        
        $images_fields = array('image1',
            'image2',
            'image3',
            'image4',
            'image5');
        
        foreach ($images_fields as $image) {
            if ($rec->{$image} == '') {
                $row->{$image} = NULL;
            } else {
                $row->{$image} = $Fancybox->getImage($rec->{$image}, $tArr, $mArr);
            }
        }
        // ENDOF fancybox ефект за картинките
    }
    
    
    /**
     * Променяме заглавието на Single
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_AfterPrepareSingleTitle($mvc, $res, $data)
    {
        $data->title = "<span style='color: green;'>".$data->row->title."</span>";
    }
    
    
    /**
     * Подготвя шаблона за единичния изглед
     *
     * @param stdClass $data
     * @deprecated
     */
    ///*
    function renderSingleLayout_($data)
    {
        if( count($this->details) ) {
            foreach($this->details as $var => $className) {
            	$detail = cls::get($className);
            	$detailsTpl .= "<fieldset>";
            	$detailsTpl .= "<legend>{$detail->title}</legend>";
                $detailsTpl .= "[#Detail{$var}#]";
            	$detailsTpl .= "</fieldset>";
            }
        }
        
        $viewProduct = cls::get('cat_tpl_SingleProduct', array('data' => $data));
        
        $viewProduct->replace(new ET($detailsTpl), 'detailsTpl');
        
        return $viewProduct;
    }
    //*/
    
    
    /**
     * Оцветяване през ред
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_AfterPrepareListRecs($mvc, $data)
    {
        $rowCounter = 0;
        
        if (count($data->rows)) {
            foreach ($data->rows as $i=>&$row) {
            	$rec = $data->recs[$i];
                if ($rowCounter % 2 != 0) {
                    $row->ROW_ATTR .= new ET(' style="background-color: #f6f6f6;"');
                }
                $rowCounter++;
                $row->title = "{$rec->code}. {$row->title}";
                $row->title = ht::createLink($row->title, array($mvc, 'single', $rec->id));
            	$row->title = "{$row->title}<div><small>{$rec->inBrief}</small></div>";
            }
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
        $data->listFilter->FNC('order', 'enum(alphabetic=Азбучно,last=Последно добавени)', 
                                        'caption=Подредба,input,silent,remember');
        $data->listFilter->setField('categoryId', 
        	'placeholder=Всички категории,caption=Категория,input,silent,mandatory=,remember');
        $data->listFilter->getField('categoryId')->type->params['allowEmpty'] = true;
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай');
        $data->listFilter->showFields = 'order,categoryId';
        $data->listFilter->input('order,categoryId', 'silent');
        
        /**
         * @todo Кандидат за плъгин - перманентни полета на форма
         * 
         * Плъгина може да се прикачи към формата, на on_AfterInput(). Трябва обаче да се
         * измисли еднозначно съответствие между име на поле на конкретна форма и името на 
         * съответната стойност в сесията. Полетата на формите са именувани, но формите не са.
         */
        
        if (!$data->listFilter->rec->categoryId && is_null(Request::get('categoryId'))) {
        	$data->listFilter->rec->categoryId = Mode::get('cat_Products::listFilter::categoryId');
        } else {
        	Mode::setPermanent('cat_Products::listFilter::categoryId', $data->listFilter->rec->categoryId);
        }
        if (!$data->listFilter->rec->order) {
        	$data->listFilter->rec->order = Mode::get('cat_Products::listFilter::order');
        } else {
        	Mode::setPermanent('cat_Products::listFilter::order', $data->listFilter->rec->order);
        }
    }
    

    /**
     * Подредба и филтър на on_BeforePrepareListRecs()
     * Манипулации след подготвянето на основния пакет данни
     * предназначен за рендиране на списъчния изглед
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_BeforePrepareListRecs($mvc, $res, $data)
    {
        // Подредба
        if($data->listFilter->rec->order == 'alphabetic' || !$data->listFilter->rec->order) {
            $data->query->orderBy('#title');
        } elseif($data->listFilter->rec->order == 'last') {
            $data->query->orderBy('#createdOn=DESC');
        }
        
        if ($data->listFilter->rec->categoryId) {
        	$data->query->where("#categoryId = {$data->listFilter->rec->categoryId}");
        }
    }
    
    
    /**
     * Перо в номенклатурите, съответстващо на този продукт
     *
     * Част от интерфейса: acc_RegisterIntf
     */
    static function getItemRec($objectId)
    {
    	$result = null;
    	
    	if ($rec = self::fetch($objectId)) {
    		$result = (object)array(
    			'num' => $rec->code,
    			'title' => $rec->title,
    			'uomId' => $rec->unitId,
    			'features' => 'foobar' // @todo!
    		);
    	}
    	
    	return $result;
    }


   /**
     * @see crm_ContragentAccRegIntf::getLinkToObj
     * @param int $objectId
     */
    static function getLinkToObj($objectId)
    {
    	if ($rec  = self::fetch($objectId)) {
    		$result = ht::createLink($rec->title, array(__CLASS__, 'Single', $objectId)); 
    	} else {
    		$result = '<i>неизвестно</i>';
    	}
    	
    	return $result;
    }
    
    /**
     * @see acc_RegisterIntf::itemInUse()
     * @param int $objectId
     */
    static function itemInUse($objectId)
    {
    }
}