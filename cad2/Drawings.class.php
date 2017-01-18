<?php


/**
 * Контролер за изчертаване на геометрични фигури
 */
class cad2_Drawings extends embed_Manager {

    
    
    /**
	 * Свойство, което указва интерфейса на вътрешните обекти
	 */
	public $driverInterface = 'cad2_ShapeIntf';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Clone, plg_Created, cad2_Wrapper, plg_Search';  
                    

    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = "Фигура";
    

    public $title = "Фигури";
    
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/draw_island.png';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,driverClass,params=Параметри,createdOn,createdBy';
    
    
    /**
     * Кой може да го отхвърли?
     */
    public $searchFields = 'name';
    
    
    /**
     * Кой може да го прочете?
     */
    public $canRead = 'cad,ceo,admin';
    
    
    /**
     * Кой може да променя?
     */
    public $canEdit = 'cad,ceo,admin';
    
    
    /**
     * Кой може да добавя?
     */
    public $canAdd = 'cad,ceo,admin';
    
        
    /**
     * Кой може да го разгледа?
     */
    public $canList = 'cad,ceo,admin';
    
    
    /**
     * Кой може да го отхвърли?
     */
    public $canReject = 'cad,ceo,admin';
    
    
    /**
     * Кой може да качва файлове
     */
    public $canWrite = 'cad,ceo,admin';
    
    
	
    /**
     * Нов темплейт за показване
     */
    //public $singleLayoutFile = 'cat/tpl/products/SingleProduct.shtml';
    
    
    /**
     * Кой има достъп до единичния изглед
     */
    public $canSingle = 'cad,ceo,admin';
    
	
   	
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar', 'caption=Наименование, remember=info,width=100%');
        $this->FLD('proto', "key(mvc=cat_Products,allowEmpty,select=name)", "caption=Прототип,input=hidden,silent,refreshForm,placeholder=Популярни продукти");
	}

    
	/**
	 * След преобразуване на записа в четим за хора вид.
	 *
	 * @param core_Mvc $mvc
	 * @param stdClass $row Това ще се покаже
	 * @param stdClass $rec Това е записа в машинно представяне
	 */
	public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
	{
		$exp = explode('»', $row->driverClass);
		
		if(count($exp) == 2){
			$row->driverClass = tr(trim($exp[0])) . " » " . tr(trim($exp[1]));
		} else {
			$row->driverClass = tr($row->driverClass);
		}
		
		if(isset($fields['-list'])){
			$row->driverClass = ht::createLink($row->driverClass, self::getSingleUrlArray($rec->id));
		}
	}
    
    
    /**
     * След рендиране на сингъла
     */
    protected static function on_AfterRenderSingle($mvc, $tpl, $data)
    { 
    	$driver = cls::get($data->rec->driverClass);
        $svg = $driver->getCanvas();
        $driver->render($svg, (array) $data->rec);
        $tpl->append('<div class="clearfix21"></div>');

        $tpl->append($svg->render());

        $tpl->append('<div class="clearfix21"></div>');

        $tpl->append($svg->debug);
    }


    /**
     * След подготвяне на формата за филтриране
     *
     * @param blast_EmailSend $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        // Подреждаме записите, като неизпратените да се по-нагоре
        $data->query->orderBy("createdOn", 'DESC');
       
        $data->listFilter->showFields = 'search';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
    }


    public static function on_AfterRead($mvc, $rec)
    {
        if(!$rec->name) {
            $rec->name = tr($mvc->getVerbal($rec, 'driverClass')) . "({$rec->id})";
        }
    }


    protected static function on_AfterPrepareSingleToolbar($mvc, $res, $data)
    {
        if(TRUE || $mvc->haveRightFor('update', $data->rec)) {
            $data->toolbar->addBtn('SVG', array($mvc, 'DownloadSvg', $data->rec->id), NULL, 'ef_icon=fileman/icons/16/svg.png');
            $data->toolbar->addBtn('PDF', array($mvc, 'DownloadPdf', $data->rec->id), NULL, 'ef_icon=fileman/icons/16/pdf.png');
        }
    }


    function act_DownloadSvg()
    {
        requireRole('ceo,admin,cad');
        $id = Request::get('id', 'int');
        $rec = self::fetch($id);
        $driver = cls::get($rec->driverClass);
        $svg = $driver->getCanvas();
        $driver->render($svg, (array) $rec);
        $fileName = trim(fileman_Files::normalizeFileName($rec->name), '_') . '';

    	header("Content-type: application/svg");
    	header("Content-Disposition: attachment; filename={$fileName}.svg");
    	header("Pragma: no-cache");
    	header("Expires: 0");

        echo $svg->render();
        shutdown();
    }


    /**
     * Сваляне на PDF
     */
    function act_DownloadPdf()
    {
        requireRole('ceo,admin,cad');
        $id = Request::get('id', 'int');
        $rec = self::fetch($id);
        $driver = cls::get($rec->driverClass);
        $svg = $driver->getCanvas();
        
        $driver->render($svg, (array) $rec);
        $fileContent = $svg->render();
        $fileName = trim(fileman_Files::normalizeFileName($rec->name), '_') . '';
        
        // Добавяме файла в кофата
        $fh = fileman::absorbStr($fileContent, 'archive', $fileName . '.svg');

        // Конвертираме
        $pdfFn = fileman_webdrv_Inkscape::toPdf($fh, TRUE);
        
        echo fileman_Files::getContent($pdfFn);

    	header("Content-type: application/pdf");
    	header("Content-Disposition: attachment; filename={$fileName}.pdf");
    	header("Pragma: no-cache");
    	header("Expires: 0");

        shutdown();
    }
}