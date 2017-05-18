<?php



/**
 * Клас 'plg_Printing' - Добавя бутони за печат
 *
 *
 * @category  ef
 * @package   plg
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class plg_Printing extends core_Plugin
{
    
    
    /**
     * Конструктор
     */
    public function __construct()
    {
        $Plugins = &cls::get('core_Plugins');
        
        $Plugins->setPlugin('core_Toolbar', 'plg_Printing');
        $Plugins->setPlugin('core_Form', 'plg_Printing');
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    public static function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        // Бутон за отпечатване
        $url = getCurrentUrl();
        
        $url['Printing'] = 'yes';

        //self::addCmdParams($url);
        
        $data->toolbar->addBtn('Печат', $url,
            'id=btnPrint,target=_blank','ef_icon = img/16/printer.png,title=Печат на страницата');
    }
    
    
    /**
     * Добавя бутон за настройки в единичен изглед
     * @param stdClass $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
    {
        // Текущото URL
    	$currUrl = getCurrentUrl();
    	
    	// Ако името на класа е текущото URL
    	if (strtolower($mvc->className) == strtolower($currUrl['Ctr'])) {
    	    
    	    // Екшъна
    	    $act = strtolower($currUrl['Act']);
    	    
    	    // Ако екшъна е single или list
    	    if ($act == 'single' || $act == 'list') {
    	        
    	        // URL за принтиране
    	        $url = $currUrl + array('Printing' => 'yes');
    	    }
    	}
    	
    	// Ако няма URL
    	if (!$url) {
    	    
    	    // Създаваме го
    	    $url = array(
                $mvc,
                'single',
                $data->rec->id,
                'Printing' => 'yes',
            );
    	}

        self::addCmdParams($url);

        if($mvc->haveRightFor('single', $data->rec)){
        	
        	// По подразбиране бутона за принтиране се показва на втория ред на тулбара
        	setIfNot($mvc->printBtnToolbarRow, 2);
        	
        	$attr = array('name' => 'btnPrint');
        	ht::setUniqId($attr);
        	
        	// Бутон за отпечатване
        	$data->toolbar->addBtn('Печат', $url, "id={$attr['id']},target=_blank,row={$mvc->printBtnToolbarRow}", 'ef_icon = img/16/printer.png,title=Печат на документа');
        }
    }
    
    
    /**
     * Извиква се преди изпълняването на екшън
     */
    public static function on_BeforeAction($mvc, &$res, $act)
    {
        if(Request::get('Printing')) {
            Mode::set('wrapper', 'page_Print');
            Mode::set('printing');
        }
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    public static function on_BeforeRenderWrapping($mvc, &$res, $tpl)
    {
        if(Request::get('Printing')) {
            
            $tpl->prepend(tr($mvc->title) . " « ", 'PAGE_TITLE');
            
            $res = $tpl;
            
            jquery_Jquery::run($tpl, "scalePrintingDocument(1180);", TRUE);
            
            return FALSE;
        }
    }
    
    
    /**
     * Предотвратява рендирането на тулбарове
     */
    public static function on_BeforeRenderHtml($mvc, &$res)
    {
        if(Request::get('Printing')) {
            
            $res = NULL;
            
            return FALSE;
        }
    }


    /**
     * Добавя ваички командни параметри от GET заявката
     */
    static function addCmdParams(&$url)
    {
        $cUrl = getCurrentUrl();

        if(count($cUrl)) {
            foreach($cUrl as $param => $value) {
                if($param{0} < 'a' || $param{0} > 'z') {
                    $url[$param] = $value;
                }
            }
        }
    }
}