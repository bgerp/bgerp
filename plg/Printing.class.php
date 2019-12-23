<?php


/**
 * Клас 'plg_Printing' - Добавя бутони за печат
 *
 *
 * @category  ef
 * @package   plg
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
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
        
        $data->toolbar->addBtn(
            
            'Печат',
            
            $url,
            'id=btnPrint,target=_blank',
            
            'ef_icon = img/16/printer.png,title=Печат на страницата'
        
        );
    }
    
    
    /**
     * Добавя бутон за настройки в единичен изглед
     *
     * @param stdClass $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
    {
        if (Mode::is('forceShowPrint') || !($data->rec->state == 'draft' ||
            ($data->rec->state == 'rejected' && $data->rec->brState == 'draft') ||
            ($data->rec->state == 'rejected' && $data->rec->brState != 'draft' && $mvc->printRejected === false))) {
            if ($mvc->haveRightFor('single', $data->rec)) {
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
                
                // По подразбиране бутона за принтиране се показва на втория ред на тулбара
                setIfNot($mvc->printBtnToolbarRow, 2);
                $printBtnId = self::getPrintBtnId($mvc, $data->rec->id);
                
                // Бутон за отпечатване
                $data->toolbar->addBtn('Печат', $url, "id={$printBtnId},target=_blank,row={$mvc->printBtnToolbarRow}", 'ef_icon = img/16/printer.png,title=Печат на документа');
            }
        }
    }
    
    
    /**
     * Какво е уникалното име на бутона за печат
     * 
     * @param mixed $mvc
     * @param int $id
     * @return string
     */
    public static function getPrintBtnId($mvc, $id)
    {
        return "btnPrint_" . cls::getClassName($mvc) . "_" . $id;
    }
    
    
    /**
     * Извиква се преди изпълняването на екшън
     */
    public static function on_BeforeAction($mvc, &$res, $act)
    {
        if (Request::get('Printing')) {
            Mode::set('wrapper', 'page_Print');
            Mode::set('printing');
        }
    }
    
    
    /**
     * Променяме шаблона в зависимост от мода
     *
     * @param core_Mvc $mvc
     * @param core_ET      $tpl
     * @param object       $data
     */
    public function on_BeforeRenderSingleLayout($mvc, &$tpl, $data)
    {
        // Ако при принтиране има указан шаблон специално за принтиране, използва се той
        if(Mode::is('printing') && isset($mvc->singleLayoutPrintFile)){
            $mvc->singleLayoutFile = $mvc->singleLayoutPrintFile;
        }
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    public static function on_BeforeRenderWrapping($mvc, &$res, $tpl)
    {
        if (Request::get('Printing')) {
            $tpl->prepend(tr($mvc->title) . ' « ', 'PAGE_TITLE');
            
            $res = $tpl;
            
            jquery_Jquery::run($tpl, 'scalePrintingDocument(1180);', true);
            
            return false;
        }
    }
    
    
    /**
     * Предотвратява рендирането на тулбарове
     */
    public static function on_BeforeRenderHtml($mvc, &$res)
    {
        if (Request::get('Printing') && !Mode::get('forcePrinting')) {
            $res = null;
            
            return false;
        }
    }
    
    
    /**
     * Изпълнява се след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     *
     * @return bool
     */
    protected static function on_AfterRenderListFilter($mvc, &$res, $data)
    {
        if ($mvc->showPrintListFilter !== false) {
            $showFieldsArr = arr::make($data->listFilter->showFields, true);
            
            $fFields = '';
            
            if ($data->listFilter && $data->listFilter->rec) {
                Mode::push('text', 'plain');
                foreach ($showFieldsArr as $showFields) {
                    $fRecVal = $data->listFilter->rec->{$showFields};
                    if (isset($fRecVal) && trim($fRecVal)) {
                        $field = $data->listFilter->fields[$showFields];
                        
                        if (!$field) {
                            continue;
                        }
                        
                        if (($field->input == 'hidden') || ($field->input == 'none')) {
                            continue;
                        }
                        
                        if ($field->printListFilter == 'none') {
                            continue;
                        }
                        
                        $fType = $field->type;
                        if (!$fType) {
                            continue;
                        }
                        
                        $caption = tr($field->caption);
                        $verbVal = $fType->toVerbal($fRecVal);
                        
                        if (!$verbVal) {
                            continue;
                        }
                        
                        $fFields .= $fFields ? ' | ' : '';
                        
                        if ($caption) {
                            $fFields .= $caption . ': ';
                        }
                        $fFields .= $verbVal;
                    }
                }
                Mode::pop('text');
            }
            
            if ($fFields) {
                $fFields = "<div class='printListFilter'>{$fFields}</div>";
                
                $res->append($fFields, '1');
            }
        }
    }
    
    
    /**
     * Добавя ваички командни параметри от GET заявката
     */
    public static function addCmdParams(&$url)
    {
        $cUrl = getCurrentUrl();
        
        if (count($cUrl)) {
            foreach ($cUrl as $param => $value) {
                if ($param{0} < 'a' || $param{0} > 'z') {
                    $url[$param] = $value;
                }
            }
        }
    }
}
