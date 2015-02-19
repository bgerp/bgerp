<?php



/**
 * Клас 'page_Internal' - Шаблон за страница на приложението, видима за вътрешни потребители
 *
 * Файлът може да се подмени с друг
 *
 *
 * @category  bgerp
 * @package   page
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Стандартна вътрешна страница
 */
class core_page_Internal extends core_page_Active {
    
    public $interfaces = 'core_page_WrapperIntf';
 
    /**
     * Конструктор за страницата по подразбиране
     * Тази страница използва internal layout, header и footer за да 
     * покаже една обща обвивка за съдържанието за вътрешни потребители
     */
    function core_page_Internal()
    {
    	// Конструиране на родителския клас
        $this->core_page_Active();
        
        bgerp_Notifications::subscribeCounter($this);
        
        // Стилове за темата
        $this->push('css/default-theme.css','CSS');

		// Добавяне на стил само за дефоултния андроидски браузър
        $browserInfo = Mode::get("getUserAgent");
        if(strPos($browserInfo, 'Mozilla/5.0') !== FALSE && strPos($browserInfo,'Android') !== FALSE && 
        strPos($browserInfo, 'AppleWebKit') !== FALSE && strPos($browserInfo,'Chrome') === FALSE){
        	  $this->append("
		       select {padding-left: 0.2em !important;}
		         ", "STYLES");
        }
        
        // Добавяне на базовия JS
        $this->push('js/overthrow-detect.js', 'JS');
        
        // Хедъри за контрол на кеша
        $this->push('Cache-Control: private, max-age=0', 'HTTP_HEADER');
        $this->push('Expires: ' . gmdate("D, d M Y H:i:s", time() + 3600) . ' GMT', 'HTTP_HEADER');
        
        // Добавяне на титлата на страницата
    	$conf = core_Packs::getConfig('core');
        $this->prepend($conf->EF_APP_TITLE, 'PAGE_TITLE');
        
        // Вкарваме съдържанието
        $this->replace(cls::get('page_InternalLayout'), 'PAGE_CONTENT');
        
        // Вкарваме  футъра
        $this->replace(cls::get('page_InternalFooter'), 'PAGE_FOOTER');
    }

    
    /**
     * Прихваща изпращането към изхода, за да постави нотификации, ако има
     */
    static function on_Output(&$invoker)
    {
        if (!Mode::get('lastNotificationTime')) {
            Mode::setPermanent('lastNotificationTime', time());
        }
    }
} 