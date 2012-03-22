<?php



/**
 * Клас 'plg_Printing' - Добавя бутони за печат
 *
 *
 * @category  all
 * @package   plg
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class plg_Printing extends core_Plugin
{
    
    
    /**
     * @todo Чака за документация...
     */
    function plg_Printing()
    {
        $Plugins = &cls::get('core_Plugins');
        
        $Plugins->setPlugin('core_Toolbar', 'plg_Printing');
        $Plugins->setPlugin('core_Form', 'plg_Printing');
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    function on_AfterPrepareListToolbar($mvc, &$res, $data)
    {
        // Бутон за отпечатване
        $url = getCurrentUrl();
        
        $url['Printing'] = 'yes';
        
        $data->toolbar->addBtn('Печат', $url,
            'id=btnPrint,target=_blank,class=print');
    }
    
    
    /**
     * Добавя бутон за настройки в единичен изглед
     * @param stdClass $mvc
     * @param stdClass $data
     */
    function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
    {
        // Бутон за отпечатване
        $data->toolbar->addBtn('Печат', array(
                $mvc,
                'single',
                $data->rec->id,
                'Printing' => 'yes',
            ),
            'id=btnPrint,target=_blank,class=print');
    }
    
    
    /**
     * Извиква се преди изпълняването на екшън
     */
    function on_BeforeAction($mvc, &$res, $act)
    {
        if(Request::get('Printing')) {
            Mode::set('wrapper', 'tpl_PrintPage');
            Mode::set('printing');
        }
    }
    
    
    /**
     * Извиква се преди рендирането на 'опаковката'
     */
    function on_BeforeRenderWrapping($mvc, &$res, $tpl)
    {
        if(Request::get('Printing')) {
            
            $tpl->append(tr($mvc->title) . " » ", 'PAGE_TITLE');
            
            $res = $tpl;
            
            return FALSE;
        }
    }
    
    
    /**
     * Предотвратява рендирането на тулбарове
     */
    function on_BeforeRenderHtml($mvc, &$res)
    {
        if(Request::get('Printing')) {
            
            $res = NULL;
            
            return FALSE;
        }
    }
}