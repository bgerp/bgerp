<?php
/**
 * Стандартна тема за външната част
 *
 * @title     Стандартна CMS тема
 *
 * @category  bgerp
 * @package   cms
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cms_DefaultTheme extends core_ProtoInner
{
    /**
     * Поддържан интерфейс
     */
    public $interfaces = 'cms_ThemeIntf';
    
    
    /**
     * Дали темата носи собствени заглавни картинки
     */
    public $haveOwnHeaderImages = false;
    
    
    /**
     * Общ лейаут за темата
     */
    public $layout = 'cms/tpl/Page.shtml';
    
    
    /**
     * Допълване на формата за домейна със специфични полета за кожата
     */
    public function addEmbeddedFields(core_FieldSet &$form)
    {
        $form->FLD('wImg1', 'fileman_FileType(bucket=gallery_Pictures)', 'caption=Ротиращи се картинки за десктоп (1000x288px)->Изображение 1');
        $form->FLD('wImg2', 'fileman_FileType(bucket=gallery_Pictures)', 'caption=Ротиращи се картинки за десктоп (1000x288px)->Изображение 2');
        $form->FLD('wImg3', 'fileman_FileType(bucket=gallery_Pictures)', 'caption=Ротиращи се картинки за десктоп (1000x288px)->Изображение 3');
        $form->FLD('wImg4', 'fileman_FileType(bucket=gallery_Pictures)', 'caption=Ротиращи се картинки за десктоп (1000x288px)->Изображение 4');
        $form->FLD('wImg5', 'fileman_FileType(bucket=gallery_Pictures)', 'caption=Ротиращи се картинки за десктоп (1000x288px)->Изображение 5');
        $form->FLD('colabImg', 'fileman_FileType(bucket=gallery_Pictures)', 'caption=Картинка за логин и при колаборатор (1000x150px)->Изображение');
        
        $form->FLD('interframeImage', 'fileman_FileType(bucket=gallery_Pictures)', 'caption=Ротиращи се картинки за десктоп (1000x288px)->Междинна');
        $form->FLD('fadeDelay', 'int', 'caption=Превключване на картинките->Задържане,suggestions=3000|5000|7000');
        $form->FLD('fadeTransition', 'int', 'caption=Превключване на картинките->Транзиция,suggestions=500|1000|1500');
        $form->FLD('nImg', 'fileman_FileType(bucket=gallery_Pictures)', 'caption=Заглавна картинка за мобилен (360x104px)->Изображение 1');
        $form->FLD('title', 'varchar(14)', 'caption=Заглавие на сайта->Име на фирмата');
        $form->FLD('subtitle', 'varchar(50)', 'caption=Заглавие на сайта->Подзаглавие');
        $form->FLD('titleColor', 'color_Type', 'caption=Заглавие на сайта->Цвят');
        
        // Фон на хедъра
        $form->FLD('headerColor', 'color_Type', 'caption=Цветове за темата->Цвят на хедъра');
        
        // Фон на менюто
        $form->FLD('baseColor', 'color_Type', 'caption=Цветове за темата->Базов цвят');
        $form->FLD('activeColor', 'color_Type', 'caption=Цветове за темата->Активен цвят');
        $form->FLD('bgColor', 'color_Type', 'caption=Цветове за темата->Фон на страницата');
    }
    
    
    public function prepareWrapper($tpl)
    {
        // Добавяме заглавната картика
        $tpl->replace($this->getHeaderImg(), 'HEADER_IMG');
        
        // Добавяме заглавния текст
        $title = $this->innerForm->title;
        
        $style = '';
        if ($this->innerForm->titleColor) {
            $style = " style='color:{$this->innerForm->titleColor};'";
        }

        if ($title) {
            $title = "<span{$style}>" . $title . '</span>';
            $tpl->replace($title, 'CORE_APP_NAME');
        }

        $subtitle = $this->innerForm->subtitle;
        if ($subtitle) {
            $subtitle = "<span{$style}>" . $subtitle . '</span>';
            $tpl->replace($subtitle, 'CORE_APP_SUBTITLE');
        }

        if ($this->innerForm->headerColor) {
            $css .= "\n    #all #cmsTop {background-color:{$this->innerForm->headerColor} !important;}";
        }
        
        if ($this->innerForm->baseColor) {
            $baseColor = ltrim($this->innerForm->baseColor, '#');
        } else {
            $baseColor = '334';
        }
        
        $bordercolor = phpcolor_Adapter::changeColor($baseColor, 'mix', 10, '666');
        
        if (phpcolor_Adapter::checkColor($baseColor, 'dark')) {
            $mixColor = '#aaa';
            $css .= "\n    #cmsBottom a, #cmsBottom a:hover, #cmsMenu a {color:#fff !important; text-shadow: 0px 0px 2px #000}";
            $css .= "\n    .vertical .formTitle, .vertical .formGroup, .vertical .formMiddleCaption, .vertical form[method=post] input[type=submit], form[method=post] .formTable input[type=submit] {color:#fff !important;}";
        } else {
            $mixColor = '#666';
            
            // стилове за тъмен цвят
            $css .= "\n     #cmsBottom a, #cmsBottom a:hover, #cmsMenu a {color:#000 !important; text-shadow: none}";
            $css .= "\n    .vertical .formTitle, .vertical .formGroup, .vertical .formMiddleCaption, .vertical form[method=post] input[type=submit], form[method=post] .formTable input[type=submit] {color:#000 !important;}";
        }
        
        if ($this->innerForm->activeColor) {
            $activeColor = ltrim($this->innerForm->activeColor, '#');
        } else {
            $colorObj = new color_Object($baseColor);
            list($r, $g, $b) = array($colorObj->r, $colorObj->g, $colorObj->b);
            
            $colorObj = new color_Object($mixColor);
            list($r1, $g1, $b1) = array($colorObj->r, $colorObj->g, $colorObj->b);
            
            
            if ($r + $g + $b) {
                $colorMultiplier = sqrt(($r1 * $r1 + $g1 * $g1 + $b1 * $b1) / ($r * $r + $g * $g + $b * $b));
                
                if ($colorMultiplier > 0.9) {
                    if ($colorMultiplier <= 1) {
                        $colorMultiplier -= 0.2;
                    } elseif ($colorMultiplier <= 1.1) {
                        $colorMultiplier += 0.2;
                    }
                }
                
                $colorObj->r = $r * $colorMultiplier;
                $colorObj->g = $g * $colorMultiplier;
                $colorObj->b = $b * $colorMultiplier;
                
                $activeColor = $colorObj->getHex('');
            } else {
                $activeColor = '333';
            }
        }
        
        
        if ($this->innerForm->bgColor) {
            $background = ltrim($this->innerForm->bgColor, '#');
        } else {
            $background = phpcolor_Adapter::changeColor($baseColor, 'lighten', 30);
            $background = phpcolor_Adapter::changeColor($background, 'mix', 1, '#fff');
        }
        
        // изчисления за фон и рамка на линковете
        if (phpcolor_Adapter::checkColor($activeColor, 'dark')) {
            $fontColor = phpcolor_Adapter::changeColor($activeColor, 'darken', 25);
            $bgcolorActive = phpcolor_Adapter::changeColor($activeColor, 'lighten', 30);
            if (color_Colors::compareColorLightness('#' . $activeColor, '#666') == -1) {
                $css .= "\n    #cmsMenu a.selected, #cmsMenu a:focus, #cmsMenu a:hover {color:#fff !important; text-shadow: 2px 2px 2px #000}";
            }
        } else {
            if (phpcolor_Adapter::checkColor($baseColor, 'dark')) {
                $fontColor = $baseColor;
            } else {
                $fontColor = phpcolor_Adapter::changeColor($baseColor, 'mix', 1, '#333');
            }
            $bgcolorActive = phpcolor_Adapter::changeColor($activeColor, 'lighten', 15);
            if (color_Colors::compareColorLightness($activeColor, '#aaa') == 1) {
                $css .= "\n    #cmsMenu a.selected, #cmsMenu a:focus, #cmsMenu a:hover {color:#000 !important; text-shadow: 0px 0px 2px #fff}";
            }
        }
        
        $visitedFontColor = phpcolor_Adapter::changeColor($fontColor, 'mix', 1, '#6611BB');
        
        if (strlen($visitedFontColor) != 6) {
            $visitedFontColor = '660099';
        }
        
        $colorObj = new color_Object($bgcolorActive);
        list($tempR, $tempG, $tempB) = array($colorObj->r, $colorObj->g, $colorObj->b);
        
        $tempBalance = ($tempR + $tempB + $tempG) / 3;
        
        if ($tempBalance < 200 && phpcolor_Adapter::changeColor($bgcolorActive, 'lighten', 20) != '#ffffff') {
            $bgcolorActive = phpcolor_Adapter::changeColor($bgcolorActive, 'lighten', 20);
        }
        
        $css .= "\n    .background-holder, body {background-color:#{$background} !important;}";
        
        $css .= "\n    #cmsMenu a.selected, #cmsMenu a:focus, #cmsMenu a:hover, .cookies .agree {background-color:#{$activeColor};}";
        
        $css .= "\n    .selected-external-tab  {border-top: 3px solid #{$activeColor} !important;}";
        
        // стилове за меню и футър
        $css .= "\n    #cmsMenu {background-color:#{$baseColor};}";
        $css .= "\n    .cookies {background-color:#{$baseColor};}";
        $css .= "\n    #cmsBottom {background-color:#{$baseColor}; border-top:1px solid #{$bordercolor} !important;border-bottom:1px solid #{$bordercolor} !important;}";
        $css .= "\n    #cmsMenu {border-top:1px solid #{$bordercolor} !important; border-bottom:1px solid #{$bordercolor} !important;}";
        
        // цветове на формите в зависимост от основния цвят
        $css .= "\n    .vertical form[method=post] input[type=submit], form[method=post] .formTable input[type=submit] {background-color:#{$baseColor} !important; border: 1px solid #{$bordercolor} !important}";
        $css .= "\n    .vertical .formTitle, .vertical .formMiddleCaption, .vertical .formGroup {background-color:#{$baseColor} !important; border-color:#{$bordercolor};}";
        
        $linkBorder = phpcolor_Adapter::changeColor($bgcolorActive, 'mix', 5, $bordercolor);
        $css .= "\n    .additionalFooter {background-color:#{$background} !important;}";
        
        // Цвятове за линковете и h2 заглавията
        $css .= "\n    #cmsNavigation .nav_item a { color: #{$fontColor};}";
        $css .= "\n    .cookies a { color: #{$bgcolorActive} !important;}";
        
        $css .= "\n    #all #maincontent .richtext a:visited, #all #maincontent .articles-menu a:visited, #all #maincontent .blogm-categories a:visited{ color: #{$visitedFontColor};}";
        $css .= "\n    #cmsNavigation .sel_page a, #cmsNavigation a:hover, .cookies .agree {background-color: #{$bgcolorActive} !important; border: 1px solid #{$linkBorder} !important; color: #{$fontColor}}";
        $css .= "\n    a:hover, .eshop-group-button:hover .eshop-group-button-title a,.additionalFooter .footer-links, .additionalFooter .footer-links a{color: #{$fontColor} !important;}";
        $css .= "\n    h2 {background-color:#{$bgcolorActive} !important; padding: 5px 10px;border:none !important}";
        $css .= "\n    .prevNextNav {border:dotted 1px #ccc; background-color:#eee; margin-top:10px;margin-bottom:7px; width:100%; display:table;}";
        $css .= "\n    .prevNextNav div {margin:5px;}";

        if ($css) {
            $tpl->append($css, 'STYLES');
        }

        // добавяме css-a за структурата
        $tpl->push('cms/css/Wide.css', 'CSS');
        // Добавяме дефолт темата за цветове
        $tpl->push('css/default-theme.css', 'CSS');
    }
    
    
    /**
     * Връща img-таг за заглавната картинка
     */
    public function getHeaderImg()
    {
        $imgs = array();
        if (!Mode::is('screenMode', 'narrow')) {
            if ((core_Users::isContractor() || (!core_Users::getCurrent('id', false) && Request::get('Act') == 'login')) && $this->innerForm->colabImg) {
                $img = new thumb_Img(array($this->innerForm->colabImg, 1000, 150, 'fileman', 'isAbsolute' => true,'mode' => 'large-no-change'));
                $imageURL = $img->getUrl('forced');
            } else {
                for ($i = 1; $i <= 5; $i++) {
                    $imgName = 'wImg' . $i;
                    if ($this->innerForm->{$imgName}) {
                        $imgs[$i] = $this->innerForm->{$imgName};
                    }
                }
                
                if (countR($imgs) > 1) {
                    $conf = core_Packs::getConfig('core');
                    
                    $banner = '';
                    
                    if ($this->innerForm->interframeImage) {
                        $img = new thumb_Img(array($this->innerForm->interframeImage, 1000, 288, 'fileman', 'isAbsolute' => true, 'mode' => 'large-no-change'));
                        $imageURL = $img->getUrl('forced');
                        $hImage = ht::createElement('img', array('src' => $imageURL, 'width' => 1000, 'height' => 288, 'alt' => $conf->EF_APP_TITLE, 'class' => 'headerImg', 'style' => 'position:absolute'));
                        $banner .= $hImage;
                    }
                    
                    $banner .= '<div class="fadein">';
                    $style = '';
                    foreach ($imgs as $iHash) {
                        $img = new thumb_Img(array($iHash, 1000, 288, 'fileman', 'isAbsolute' => true, 'mode' => 'large-no-change'));
                        $imageURL = $img->getUrl('forced');
                        $hImage = ht::createElement('img', array('src' => $imageURL, 'width' => 1000, 'height' => 288, 'alt' => $conf->EF_APP_TITLE, 'class' => 'headerImg', 'style' => $style));
                        $banner .= "\n{$hImage}";
                        $style = 'display:none;';
                    }
                    $banner .= '</div>';
                    $banner = new ET($banner);
                    $fadeTransition = $this->innerForm->fadeTransition ? $this->innerForm->fadeTransition : 1500;
                    $fadeDelay = $this->innerForm->fadeDelay ? $this->innerForm->fadeDelay : 5000;
                    $banner->append('.fadein { position:relative; display:block; max-height:100%; max-width:100%} .fadein img {position:relative; left:0; top:0;}', 'STYLES');
                    jquery_Jquery::run($banner, "fadeImages({$fadeTransition}, {$fadeDelay});", true);
                    
                    $this->haveOwnHeaderImages = true;
                    
                    return $banner;
                }
            }
        } else {
            if ($this->innerForm->nImg) {
                $imgs[1] = $this->innerForm->nImg;
            }
        }
        
        $imgsCnt = countR($imgs);
        
        if ($imgsCnt) {
            
            // Ключа да започава от 1 до броя
            $imgs = array_combine(range(1, $imgsCnt), array_values($imgs));
            
            $img = $imgs[rand(1, countR($imgs))];
            
            if ($img) {
                if (!Mode::is('screenMode', 'narrow')) {
                    $img = new thumb_Img(array($img, 1000, 288, 'fileman', 'isAbsolute' => true, 'mode' => 'large-no-change'));
                } else {
                    $img = new thumb_Img(array($img, 360, 104, 'fileman', 'isAbsolute' => true, 'mode' => 'large-no-change'));
                }
                $imageURL = $img->getUrl('forced');
                $this->haveOwnHeaderImages = true;
            }
        }
        
        // Да покаже дефолт картинките, ако няма зададени
        if (!$imageURL) {
            $imageURL = sbf($this->getDefaultHeaderImagePath(), '');
        }
        
        $conf = core_Packs::getConfig('core');
        $hImage = ht::createElement('img', array('src' => $imageURL, 'alt' => $conf->EF_APP_TITLE, 'class' => 'headerImg'));
        
        return $hImage;
    }
    
    
    /**
     * Връща пътя до картинката за главата на публичната страница
     */
    private function getDefaultHeaderImagePath()
    {
        if (!Mode::is('screenMode', 'wide')) {
            $screen = '-narrow';
        } else {
            $screen = '';
        }
        
        $lg = '-' . cms_Content::getLang();
        
        $path = "cms/img/header{$screen}{$lg}.jpg";
        
        if (!getFullPath($path)) {
            $path = "cms/img/header{$screen}.jpg";
            if (!getFullPath($path)) {
                $path = 'cms/img/header.jpg';
                if (!getFullPath($path)) {
                    if (Mode::is('screenMode', 'wide')) {
                        $path = 'cms/img/bgERP.jpg';
                    } else {
                        $path = 'cms/img/bgERP-small.jpg';
                    }
                }
            }
        }
        
        // Дали си носим картинките по друг начин?
        if (defined('EF_PRIVATE_PATH') && file_exists(EF_PRIVATE_PATH . '/' . $path)) {
            $this->haveOwnHeaderImages = true;
        }
        
        return $path;
    }
    
    
    /**
     * 
     * @param cms_DefaultTheme $mvc
     * @param mixed $innerStateField
     * @param mixed $innerFormField
     * @param stdClass $rec
     * @param mixed $fields
     * @param mixed $mode
     */
    public static function on_BeforeSave($mvc, &$innerStateField, &$innerFormField, $rec, $fields = null, $mode = null)
    {
        if (!trim($innerFormField->title) && !$rec->id && core_Users::isSystemUser()) {
            if (!$innerFormField) {
                $innerFormField = new stdClass();
            }
            
            $innerFormField->title = core_Setup::get('EF_APP_TITLE', true);
        }
    }
    
    
    /**
     * Подготвя формата за въвеждане на данни за вътрешния обект
     * 
     * {@inheritDoc}
     * @see core_ProtoInner::prepareEmbeddedForm()
     */
    public function prepareEmbeddedForm(core_Form &$form)
    {
        if (!$form->rec->id) {
            $form->setDefault('title', core_Setup::get('EF_APP_TITLE', true));
        }
    }
}
