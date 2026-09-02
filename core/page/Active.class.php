<?php


/**
 * Клас 'cms_Page_Active' - Шаблон за публична страница
 *
 * Файлът може да се подмени с друг
 *
 *
 * @category  ef
 * @package   page
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Стандартна публична страница
 */
class core_page_Active extends page_Html
{
    public $interfaces = 'core_page_WrapperIntf';


    /**
     * Стандартни действия, при които се показва форма за въвеждане
     */
    protected static $inputFormActions = array(
        'add',
        'edit',
        'config',
        'manage',
        'save',
        'changefields',
        'clonefields',
        'editsection',
        'new',
        'modify',
        'insert',
        'createproduct',
        'replaceproduct',
        'transferinnewcontragent',
    );
    
    
    /**
     * Конструктор за страницата по подразбиране
     * Тази страница използва internal layout, header и footer за да
     * покаже една обща обвивка за съдържанието за вътрешни потребители
     */
    public function __construct()
    {
        // Конструктора на родителския клас
        parent::__construct();
        
        // Кодировка - UTF-8
        $this->replace('UTF-8', 'ENCODING');
        
        $this->push('css/common.css', 'CSS');
        $this->push('css/Application.css', 'CSS');
        $this->push('css/internalTheme.css', 'CSS');
        
        jquery_Jquery::enable($this);
        $this->push('js/efCommon.js', 'JS');

        $faviconUrl = getBoot(true, true, true) . '/favicon.ico';
        $addEditIndicator = false;
        $action = strtolower(Request::get('Act') ?? '');
        if (Mode::is('renderedInputForm') || in_array($action, self::$inputFormActions, true)) {
            $addEditIndicator = true;
            if (cls::load('cms_Domains', true)) {
                $faviconUrl = cms_Domains::getEditFaviconUrl($addEditIndicator);
            }
        }

        $this->appendOnce("\n<link rel=\"shortcut icon\" href=\"" . ht::escapeAttr($faviconUrl) . '" type="image/x-icon">', 'HEAD');
        if ($addEditIndicator) {
            jquery_Jquery::run($this, 'setEditFavIcon(' . json_encode($faviconUrl) . ');');
        }
    }
}
