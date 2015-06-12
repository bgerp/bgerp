<?php



/**
 * 
 * 
 * @category  bgerp
 * @package   colab
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class colab_plg_Document extends core_Plugin
{
    
    
	/**
     * Извиква се след описанието на модела
     */
    function on_AfterDescription(&$mvc)
    {
        // Ако има cid и Tab, показваме детайлите
        if (Request::get('Cid') && Request::get('Tab')) {
            
            // Ако има данни, преобразуваме ги в масив
            $mvc->details = arr::make($mvc->details);
            
            // Детайлите
            $mvc->details['View'] = 'colab_DocumentLog';
        }
    }
    
    
    /**
     * 
     * 
     * @param core_Master $mvc
     * @param core_ET $tpl
     * @param object $data
     */
    function on_AfterPrepareSingle($mvc, $res, $data)
    {
        if (!Mode::is('text', 'xhtml') && !Mode::is('printing') && core_Users::isPowerUser()) {
            
            if ($mvc->visibleForPartners == 'yes') {
                
                // Може и да се провери стойноста на `visibleForPartners` в `doc_Containers`
                
                $data->row->documentSettings = colab_DocumentLog::renderViewedLink($data->rec->containerId);
            }
        }
        
        // Ако е контрактор, маркираме документа като видян
        if (core_Users::isContractor()) {
            colab_DocumentLog::markAsViewed($data->rec->containerId);
        }
    }
}
