<?php



/**
 * Клас 'page_Wrapper' - Опаковка на страниците
 *
 *
 * @category  ef
 * @package   page
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_page_Wrapper extends core_BaseClass
{
    
    
    /**
     * Прави стандартна 'обвивка' на изгледа
     */
    public function render_($content)
    {
        // Определяме каква да е темата обвивката на страницата
        if (!($tplName = Mode::get('wrapper'))) {
            if (Mode::is('printing')) {
                $tplName = 'page_Print';
            } elseif (haveRole('admin,ceo,manager,officer,executive')) {
                $tplName = 'core_page_Internal';
            } else {
                $tplName = 'cms_page_External';
            }
        }
        
        // Определяне на темите, в случаите, когато се изисква външната или вътрешната обвивка
        $coreConf = core_Packs::getConfig('core');
        if ($tplName == 'core_page_Internal' && $coreConf->CORE_PAGE_WRAPPER) {
            $tplName = $coreConf->CORE_PAGE_WRAPPER;
        }
        
        // Зареждаме опаковката
        $wrapperTpl = cls::get($tplName);
        
        $wrapperTpl->prepare();

        self::replaceSpecialPlaceholders($wrapperTpl);
        
        // Изпращаме на изхода опаковано съдържанието
        $wrapperTpl->replace($content, 'PAGE_CONTENT');

        $wrapperTpl->output();
    }
    
    
    /**
     * Замества специалните плейсхолдъри
     *
     * @param core_ET|string $tpl
     */
    protected static function replaceSpecialPlaceholders(&$tpl)
    {
        if (!($tpl instanceof core_ET)) {
            
//            return $tpl;
            
            $tpl = new ET($tpl);
        }
        
        // Вземаме плейсхолдерите
        $placeHolders = $tpl->getPlaceHolders();
        
        // Заместваме специалните плейсхолдери, със съдържанието към което те сочат
        foreach ($placeHolders as $place) {
            $method = explode('::', $place);

            if (count($method) != 2) {
                continue;
            }

            $html = call_user_func($method);
            
            if ($html instanceof core_ET) {
                $content = $html->getContent(null, 'CONTENT', false, false);
            } else {
                $content = $html;
            }
            
            if (strpos($content, '::') !== false) {
                self::replaceSpecialPlaceholders($html);
            }
            
            $tpl->replace($html, $place);
        }
    }
}
