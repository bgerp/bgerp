<?php


/**
 * Общ родител на темите за външната част
 *
 *
 * @category  bgerp
 * @package   cms
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cms_ProtoTheme extends core_ProtoInner
{
    /**
     * Темата на текущия домейн или празна тема, ако няма такава
     *
     * @return cms_ProtoTheme
     */
    public static function getCurrent()
    {
        $skin = cms_Domains::getCmsSkin();
        if ($skin instanceof self) {

            return $skin;
        }

        return cls::get(__CLASS__);
    }


    /**
     * Облича страницата на външната част с темата - подготовка и ресурсите за всяка страница
     *
     * @param object|null $skin - темата на домейна
     * @param core_ET     $tpl
     */
    public static function prepareSkin($skin, $tpl)
    {
        if (!is_object($skin)) {

            return;
        }

        $skin->prepareWrapper($tpl);
        if ($skin instanceof self) {
            $skin->addAssets($tpl, 'page');
        }
    }


    /**
     * Подготвя шаблона на страницата
     *
     * @param core_ET $tpl
     */
    public function prepareWrapper($tpl)
    {
    }


    /**
     * Папка с шаблоните на темата; наследникът задава своя и държи в нея само различните
     */
    public $tplDir;


    /**
     * Шаблоните на модулите, които темата подменя със свои от tplDir; наследникът добавя своите
     */
    protected $templateMap = array();


    /**
     * Намерените шаблони в рамките на хита
     */
    protected $templatePaths = array();


    /**
     * Наследник със свой layout в tplDir не трябва да сменя и пътя
     */
    public function __construct($params = null)
    {
        parent::__construct($params);
        if (!empty($this->layout)) {
            $this->layout = $this->getTemplate($this->layout);
        }
    }


    /**
     * Шаблонът, който темата ползва вместо подадения - от най-близката папка по веригата от теми
     *
     * @param string $path
     *
     * @return string
     */
    public function getTemplate($path)
    {
        $path = (string) $path;
        if (isset($this->templatePaths[$path])) {

            return $this->templatePaths[$path];
        }

        $dirs = array_reverse(array_unique($this->getInheritedValues('tplDir')));
        $name = $this->getTemplateName($path, $dirs);

        $res = $path;
        if (isset($name)) {
            foreach ($dirs as $dir) {
                if (getFullPath("{$dir}/{$name}.shtml")) {
                    $res = "{$dir}/{$name}.shtml";
                    break;
                }
            }
        }
        $this->templatePaths[$path] = $res;

        return $res;
    }


    /**
     * Името на шаблона в папките на темите или null, ако темата не го подменя
     *
     * @param string $path
     * @param array  $dirs - папките на темите
     *
     * @return string|null
     */
    protected function getTemplateName($path, $dirs)
    {
        foreach ($dirs as $dir) {
            if (strpos($path, "{$dir}/") === 0) {

                return basename($path, '.shtml');
            }
        }
        if (in_array($path, $this->getInheritedValues('layout'), true)) {

            return basename($path, '.shtml');
        }
        $map = array_merge(array(), ...$this->getInheritedValues('templateMap'));

        return $map[$path] ?? null;
    }


    /**
     * Пътят на вложен в шаблон файл - подменят се само файловете от папките на темите
     *
     * @param string $path
     *
     * @return string
     */
    public function resolveIncludePath($path)
    {
        foreach ($this->getInheritedValues('tplDir') as $dir) {
            if (strpos((string) $path, "{$dir}/") === 0) {

                return $this->getTemplate($path);
            }
        }

        return $path;
    }


    /**
     * Стиловете, скриптовете и класовете на body по раздели на сайта (page е за всяка страница)
     * Наследникът добавя своите към тези на родителите си, а изброените в exclude (файлове, класове или ключове от getScripts()) маха
     */
    public $assets = array();


    /**
     * Добавя ресурсите на темата за раздел от сайта (page, shop, checkout, forum, blog, article, feeds)
     *
     * @param core_ET $tpl
     * @param string  $section
     */
    public function addAssets($tpl, $section)
    {
        $css = $js = $body = $excluded = array();
        foreach ($this->getInheritedValues('assets') as $assets) {
            $sectionAssets = $assets[$section] ?? array();
            $css = array_merge($css, $sectionAssets['css'] ?? array());
            $js = array_merge($js, $sectionAssets['js'] ?? array());
            $body = array_merge($body, explode(' ', $sectionAssets['body'] ?? ''));

            // Изключеното от едно ниво може да се върне от по-долно
            $exclude = $sectionAssets['exclude'] ?? array();
            $excluded = array_merge($excluded, $exclude);
            $css = array_diff($css, $exclude);
            $js = array_diff($js, $exclude);
            $body = array_diff($body, $exclude);
        }

        foreach (array_unique($css) as $file) {
            $tpl->push($file, 'CSS');
        }
        foreach (array_unique($js) as $file) {
            $tpl->push($file, 'JS');
        }
        foreach (array_unique(array_filter($body)) as $class) {
            $tpl->appendOnce(" {$class}", 'BODY_CLASS_NAME');
        }
        foreach (array_diff_key($this->getScripts($section, $js), array_flip($excluded)) as $script) {
            jquery_Jquery::run($tpl, $script);
        }
    }


    /**
     * Инициализиращите скриптове на раздел по ключ; ключът в exclude на $assets ги маха
     *
     * @param string $section
     * @param array  $jsFiles - заредените JS файлове на раздела
     *
     * @return array
     */
    public function getScripts($section, $jsFiles)
    {
        return array();
    }


    /**
     * Стойностите на свойство по веригата от класове - от най-горния родител до темата
     *
     * @param string $name
     *
     * @return array
     */
    protected function getInheritedValues($name)
    {
        $res = array();
        $class = get_class($this);
        while ($class) {
            $vars = get_class_vars($class);
            if (isset($vars[$name]) && $vars[$name] !== array()) {
                array_unshift($res, $vars[$name]);
            }
            $class = get_parent_class($class);
        }

        return $res;
    }


    /**
     * Размер на тъмбнейла в списъка с артикули на магазина - ширина и височина
     */
    public $productThumbSize = array(240, 240);


    /**
     * Параметри на изскачащия прозорец за вход
     */
    public $loginWindowFeatures = 'width=484,height=303,resizable=no,scrollbars=no';


    /**
     * Надписите на страниците в блога - към по-старите и към по-новите статии
     *
     * @return array
     */
    public function getBlogPagerLabels()
    {
        return array('« по-стари', 'по-нови »');
    }


    /**
     * Заглавието на месец в архива на блога
     *
     * @param int $month
     * @param int $year
     *
     * @return string
     */
    public function getArchiveMonthTitle($month, $year)
    {
        return dt::getMonth($month, Mode::is('screenMode', 'narrow') ? 'M' : 'F') . '/' . $year;
    }


    /**
     * Надписите на бутоните в количката - към магазина и изчистване
     *
     * @return array
     */
    public function getCartToolbarLabels()
    {
        return array(tr('Магазин'), tr('Изчистване'));
    }


    /**
     * Съдържанието и класът на линка към количката в менюто
     *
     * @param core_ET $tpl       - стандартното съдържание
     * @param string  $className - класът на линка
     * @param string  $cartName  - името на количката
     * @param string  $count     - броят артикули
     *
     * @return core_ET
     */
    public function prepareCartLink($tpl, &$className, $cartName, $count)
    {
        return $tpl;
    }


    /**
     * Атрибутите на бутона за любим артикул
     *
     * @param array $attr
     * @param bool  $isIn - дали артикулът е в любими
     */
    public function prepareFavouriteBtn(&$attr, $isIn)
    {
    }


    /**
     * Съдържанието на линка за споделяне в социална мрежа
     *
     * @param string $img - иконата на мрежата
     * @param int    $cnt - броят споделяния
     *
     * @return string
     */
    public function getSharingLinkContent($img, $cnt)
    {
        return "{$img} <sup>+</sup>{$cnt}";
    }


    /**
     * Подканата за вход във форма на външната част
     *
     * @param core_ET $info      - шаблонът с място [#link#]
     * @param string  $loginHtml - линкът за вход
     * @param string  $js        - скриптът, който отваря прозореца за вход
     */
    public function prepareLoginNote(&$info, &$loginHtml, $js)
    {
    }


    /**
     * Обработва съдържанието на статия от cms-а преди показване
     *
     * @param core_ET       $content
     * @param stdClass|null $rec
     */
    public function prepareArticleContent($content, $rec)
    {
    }


    /**
     * Линкът за редакция на статия в навигацията или null
     *
     * @param cms_Articles $mvc
     * @param stdClass     $rec
     *
     * @return core_ET|string|null
     */
    public function getArticleEditLink($mvc, $rec)
    {
        if ($mvc->haveRightFor('changerec', $rec)) {

            return $mvc->getChangeLink($rec->id);
        }

        return null;
    }


    /**
     * Мястото в шаблона на страницата за новина на посочената позиция
     *
     * @param string  $position        - позицията на новината
     * @param string  $placeholderName - мястото по подразбиране
     * @param core_ET $tpl             - шаблонът на страницата
     *
     * @return string
     */
    public function getNewsbarPlace($position, $placeholderName, $tpl)
    {
        return $placeholderName;
    }


    /**
     * Позициите за новини, които темата предлага във формата
     *
     * @param array    $positions
     * @param stdClass $rec - записът на новината
     */
    public function prepareNewsbarPositions(&$positions, $rec)
    {
    }


    /**
     * Обвивка на инструментите в менюто - вход и езици
     *
     * @param core_ET $tools
     *
     * @return core_ET
     */
    public function wrapMenuTools($tools)
    {
        return $tools;
    }


    /**
     * Съдържанието на линка за вход в менюто
     *
     * @param string $title - заглавието на линка
     *
     * @return string|core_ET
     */
    public function getMenuLoginContent(&$title)
    {
        $dRec = cms_Domains::getPublicDomain('form');

        if (haveRole('user')) {
            $filePath = 'img/32/inside';
            $title = 'Меню||Menu';
        } else {
            $filePath = 'img/32/login';
            $title = 'Вход||Log in';
        }

        if ((isset($dRec->baseColor) && phpcolor_Adapter::checkColor($dRec->baseColor) && Request::get('Ctr') != 'core_Users') ||
            (isset($dRec->activeColor) && phpcolor_Adapter::checkColor($dRec->activeColor) && Request::get('Ctr') == 'core_Users')) {
            $filePath .= 'Dark';
        } else {
            $filePath .= 'Light';
        }

        if (Mode::is('screenMode', 'narrow')) {
            $filePath .= 'M';
        }

        $filePath .= '.png';

        return ht::createImg(array('path' => $filePath, 'alt' => 'login'));
    }


    /**
     * Избора на език в менюто
     *
     * @param core_ET $tpl
     * @param array   $usedLangsArr - езиците с действащи менюта
     */
    public function renderMenuLangs($tpl, $usedLangsArr)
    {
        if (countR($usedLangsArr) == 2) {
            $lang = cms_Content::getLang();
            foreach ($usedLangsArr as $lg) {
                if ($lg == $lang) {
                    continue;
                }

                $attr = array('title' => drdata_Languages::fetchField("#code = '{$lg}'", 'nativeName'), 'id' => 'set-lang-' . $lg, 'class' => 'langIcon');
                $img = ' ';
                if (getFullPath('img/flags/' . $lg . '.png')) {
                    $img = ht::createElement('img', array('src' => sbf('img/flags/' . $lg . '.png', ''), 'alt' => $lg));
                }

                $tpl->append(ht::createLink($this->getMenuLangContent($img, $lg), array('cms_Content', 'SelectLang', 'lang' => $lg), null, $attr));
            }
        } elseif (countR($usedLangsArr) > 1) {
            $attr = array();
            $attr['class'] = 'selectLang langIcon';
            $attr['title'] = implode(', ', $usedLangsArr);
            if (Request::get('Ctr') == 'cms_Content' && Request::get('Act') == 'selectLang') {
                $attr['class'] = 'selected langIcon';
            }
            $tpl->append(ht::createLink(ht::createElement('img', array('src' => sbf('img/24/globe.png', ''))), array('cms_Content', 'selectLang'), null, $attr));
        }
    }


    /**
     * Съдържанието на линка към друг език в менюто
     *
     * @param core_ET|string $img - флагът на езика
     * @param string         $lg  - кодът на езика
     *
     * @return core_ET|string
     */
    public function getMenuLangContent($img, $lg)
    {
        return $img;
    }


    /**
     * @deprecated cms_ProtoTheme::addAssets($tpl, 'shop')
     */
    public function addShopAssets($tpl)
    {
        $this->addAssets($tpl, 'shop');
    }


    /**
     * @deprecated cms_ProtoTheme::addAssets($tpl, 'forum')
     */
    public function addForumAssets($tpl)
    {
        $this->addAssets($tpl, 'forum');
    }
}
