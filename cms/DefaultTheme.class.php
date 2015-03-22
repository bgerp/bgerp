<?php
/**
 * Стандартна тема за външната част
 * 
 * @title     Стандартна CMS тема
 * @category  bgerp
 * @package   cms
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cms_DefaultTheme extends core_ProtoInner {
    

    /**
     * Поддържан интерфейс
     */
    public $interfaces = 'cms_ThemeIntf';
    

    /**
     * Дали темата носи собствени заглавни картинки
     */
    public $haveOwnHeaderImages = FALSE;

    /**
     * Допълване на формата за домейна със специфични полета за кожата
     */
    public function addEmbeddedFields($form)
    {
        $form->FLD('wImg1', 'fileman_FileType(bucket=gallery_Pictures)', "caption=Заглавни картинки за десктоп (1000x288px)->Изображение 1");
        $form->FLD('wImg2', 'fileman_FileType(bucket=gallery_Pictures)', "caption=Заглавни картинки за десктоп (1000x288px)->Изображение 2");
        $form->FLD('wImg3', 'fileman_FileType(bucket=gallery_Pictures)', "caption=Заглавни картинки за десктоп (1000x288px)->Изображение 3");
        $form->FLD('wImg4', 'fileman_FileType(bucket=gallery_Pictures)', "caption=Заглавни картинки за десктоп (1000x288px)->Изображение 4");
        $form->FLD('wImg5', 'fileman_FileType(bucket=gallery_Pictures)', "caption=Заглавни картинки за десктоп (1000x288px)->Изображение 5");
        $form->FLD('nImg', 'fileman_FileType(bucket=gallery_Pictures)', "caption=Заглавна картинка за мобилен (360x104px)->Изображение 1");
        $form->FLD('title', 'varchar(20)', "caption=Заглавие на сайта->Текст");
        $form->FLD('titleColor', 'color_Type', "caption=Заглавие на сайта->Цвят");


        // Фон на страницата
        $form->FLD('bodyB', 'color_Type', "caption=Фон на страницата->Цвят");

        // Фон на менюто 
        $form->FLD('menuB', 'color_Type', "caption=Фон на менюто->Цвят");

        // Фон на избраното меню
        $form->FLD('menuSelB', 'color_Type', "caption=Фон на избраното меню->Цвят");
        
        // Фон на футъра 
        $form->FLD('footerB', 'color_Type', "caption=Фон на футъра->Цвят");
            
        // Връзки от страничната навигация
        $form->FLD('navLinkC', 'color_Type', "caption=Връзки от страничната навигация->Цвят");
        
        // Връзки от страничната навигация - избран
        $form->FLD('navLinkSelC', 'color_Type', "caption=Избрана връзка от страни->Цвят");

        // Фон на селектирания линк от страничната навигация
        $form->FLD('navLinkSelB', 'color_Type', "caption=Избрана връзка от страни->Фон");

        // Фон на заглавие h2 
        $form->FLD('h2B', 'color_Type', "caption=Фон на заглавие h2->Цвят");

        // Фон на заглавна лента на форма
        $form->FLD('formTitleB', 'color_Type', "caption=Фон на заглавна лента на форма->Цвят");
        
        // Rамка на заглавна лента на форма
        $form->FLD('formTitleBorderC', 'color_Type', "caption=Rамка на заглавна лента на форма->Цвят");

        // Фон на разделите във форма
        $form->FLD('formSectionB', 'color_Type', "caption=Фон на разделите във форма->Цвят");

        // Фон на бутоните във форма
        $form->FLD('formButtonB', 'color_Type', "caption=Фон на бутоните във форма->Цвят");
 
        // Рамка на бутоните във форма
        $form->FLD('formButtonBorderC', 'color_Type', "caption=Рамка на бутоните във форма->Цвят");
    }

    
    public function prepareWrapper($tpl)
    {   
        // Добавяме заглавната картика
        $tpl->replace($this->getHeaderImg(), 'HEADER_IMG');
        
        // Добавяме заглавния текст
        $title = $this->formRec->title;
        if(!$this->haveOwnHeaderImages && !$title) {
            $conf = core_Packs::getConfig('core');
            $title = $conf->EF_APP_TITLE;
        } elseif($title) {
            $title = "<span style='color:{$this->formRec->titleColor}'>" . $title . "</span>";
        }

        if($title) {
            $tpl->replace($title, 'CORE_APP_NAME');
        }
        

        // Фон на страницата
        if($this->formRec->bodyB) {
            $css .= "\n    body {background-color:{$this->formRec->bodyB};}";
        }

        // Фон на менюто 
        if($this->formRec->menuB) {
            $css .= "\n    #cmsMenu {background-color:{$this->formRec->menuB};}";
        }

        // Фон на футъра 
        if($this->formRec->footerB) {
            $css .= "\n    #cmsBottom {background-color:{$this->formRec->footerB};}";
        }
            
        // Фон на линка на текущата страница
        if($this->formRec->menuSelB) {
            $css .= "\n    #cmsMenu a.selected {background-color:{$this->formRec->menuSelB};}";
        }

        // Цвят на линковете от страничната навигация
        if($this->formRec->navLinkC) {
            $css .= "\n    #cmsNavigation .nav_item a {color:{$this->formRec->navLinkC};}";
        }
        
        // Цвят на селектирания линк от страничната навигация
        if($this->formRec->navLinkSelC) {
            $css .= "\n    #cmsNavigation .sel_page a {color:{$this->formRec->navLinkSelC};}";
        }

        // Фон на селектирания линк от страничната навигация
        if($this->formRec->navLinkSelB) {
            $css .= "\n    #cmsNavigation .sel_page a {background-color:{$this->formRec->navLinkSelB};}";
        }

        // Фон на заглавие h2 
        if($this->formRec->h2B) {
            $css .= "\n    .richtext h2 {background-color:{$this->formRec->h2B};}";
        }

        // Фон и рамка на заглавна лента на форма
        if($this->formRec->h2B) {
            $css .= "\n    .richtext h2 {background-color:{$this->formRec->h2B};}";
        }

        // Фон на заглавна лента на форма
        if($this->formRec->formTitleB) {
            $css .= "\n    .vertical .formTitle {background-color:{$this->formRec->formTitleB};}";
        }
        
        // Rамка на заглавна лента на форма
        if($this->formRec->formTitleBorderC) {
            $css .= "\n    .vertical .formTitle {border: solid 1px {$this->formRec->formTitleBorderC};}";
        }

        // Фон на разделите във форма
        if($this->formRec->formSectionB) {
            $css .= "\n    .formGroup {background-color:{$this->formRec->formSectionB} !important;}";
        }

        // Фон на бутоните във форма
        if($this->formRec->formButtonB) {
            $css .= "\n    .vertical form[method=post] input[type=submit], form[method=post] input:first-child[type=submit] {background-color:{$this->formRec->formButtonB};}";
        }

        // Рамка на бутоните във форма
        if($this->formRec->formButtonBorderC) {
            $css .= "\n    .vertical form[method=post] input[type=submit], form[method=post] input:first-child[type=submit] {border: solid 1px {$this->formRec->formButtonBorderC};}";
        }
 
        if($css) {
            $tpl->append($css, 'STYLES');
        }
        
        // Добавяме дефолт темата за цветове
        $tpl->push('css/default-theme.css', 'CSS');

    }
    

    /**
     * Връща img-таг за заглавната картинка
     */
    function getHeaderImg()
    {
        if(!Mode::is('screenMode', 'narrow')) {
            for($i = 1; $i <=5; $i++) {
                $imgName = 'wImg' . $i;
                if($this->formRec->{$imgName}) {
                    $imgs[$i] = $this->formRec->{$imgName};
                }
            }

            if(count($imgs) > 1) {
                $conf = core_Packs::getConfig('core');
                $baner = "<div id=\"slider\"><ul>"; 
                foreach($imgs as $iHash) {
                    $img = new thumb_Img(array($iHash, 1000, 288, 'fileman', 'isAbsolute' => TRUE, 'mode' => 'large-no-change'));
                    $imageURL = $img->getUrl('forced');
                    $hImage = ht::createElement('img', array('src' => $imageURL, 'width' => 1000, 'height' => 288, 'alt' => $conf->EF_APP_TITLE, 'class' => 'headerImg'));
                    $baner .= "<li>{$hImage}</li>";

                }
                $baner .= "</ul></div>";
                $baner = new ET($baner);
                $baner->append("#slider{position:relative;overflow:auto;width:100%;height:100%} #slider ul{padding:0px;margin:0px;width:100%;height:100%} #slider li{list-style:none;} #slider ul li{float:left;width:100%;height:100%} #slider ul li img {}", "STYLES");
                $baner->appendOnce(self::getSliderJS(), 'SCRIPTS');
                $baner->appendOnce("\n runOnLoad(function(){\$('#slider').unslider({fluid: true, delay: 5000});});", 'SCRIPTS');
                
                $this->haveOwnHeaderImages = TRUE;

                return $baner;
            }

        } else {
            if ($this->formRec->nImg) {
                $imgs[1] = $this->formRec->nImg;
            }
            
        }
 
        if(count($imgs)) {
            $img = $imgs[rand(1, count($imgs))];
            if(!Mode::is('screenMode', 'narrow')) {
                $img = new thumb_Img(array($img, 1000, 288, 'fileman', 'isAbsolute' => TRUE, 'mode' => 'large-no-change'));
            } else {
                $img = new thumb_Img(array($img, 360, 104, 'fileman', 'isAbsolute' => TRUE, 'mode' => 'large-no-change'));
            }
            $imageURL = $img->getUrl('forced');
            $this->haveOwnHeaderImages = TRUE;
        }
         
        // Да покаже дефолт картинките, ако няма зададени
        if(!$imageURL) {
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
    	if(!Mode::is('screenMode', 'wide')) {
    		$screen = '-narrow';
    	} else {
    		$screen = '';
    	}
    	
    	$lg = '-' . cms_Content::getLang();
    	
    	$path = "cms/img/header{$screen}{$lg}.jpg";
    	
    	if(!getFullPath($path)) {
    		$path = "cms/img/header{$screen}.jpg";
    		if(!getFullPath($path)) {
    			$path = "cms/img/header.jpg";
    			if(!getFullPath($path)) {
    				if(Mode::is('screenMode', 'wide')) {
    					$path = "cms/img/bgERP.jpg";
    				} else {
    					$path = "cms/img/bgERP-small.jpg";
    				}
    			}
    		}
    	}
        
        // Дали си носим картинките по друг начин?
        $conf = core_Packs::getConfig('core');
        if ($conf->EF_PRIVATE_PATH && file_exists($conf->EF_PRIVATE_PATH . "/" . $path)) {
            $this->haveOwnHeaderImages = TRUE;
        }

    	return $path;
    }

    static function getSliderJS()
    {
        $res .= "\n(function(e,t){if(!e)return t;var n=function(){this.el=t;this.items=t;this.sizes=[];this.max=[0,0];this.current=0;this.interval=t;this.opts=" .
                "{speed:500,delay:3e3,complete:t,keys:!t,dots:t,fluid:t};var n=this;this.init=function(t,n){this.el=t;this.ul=t.children(\"ul\");" .
                "this.max=[t.outerWidth(),t.outerHeight()];this.items=this.ul.children(\"li\").each(this.calculate);this.opts=e.extend(this.opts,n);this.setup();" . 
            "return this};this.calculate=function(t){var r=e(this),i=r.outerWidth(),s=r.outerHeight();n.sizes[t]=[i,s];if(i>n.max[0])n.max[0]=i;if(s>n.max[1])" .
            "n.max[1]=s};this.setup=function(){this.el.css({overflow:\"hidden\",width:n.max[0],height:this.items.first().outerHeight()});this.ul.css(" .
            "{width:this.items.length*100+\"%\",position:\"relative\"});this.items.css(\"width\",100/this.items.length+\"%\");if(this.opts.delay!==t){this.start();" .
            "this.el.hover(this.stop,this.start)}this.opts.keys&&e(document).keydown(this.keys);this.opts.dots&&this.dots();if(this.opts.fluid){var r=function()" .
            "{n.el.css(\"width\",Math.min(Math.round(n.el.outerWidth()/n.el.parent().outerWidth()*100),100)+\"%\")};r();e(window).resize(r)}if(this.opts.arrows)" .
            "{this.el.parent().append('<p class=\"arrows\"><span class=\"prev\">â†</span><span class=\"next\">â†’</span></p>').find(\".arrows span\").click(function()" .
            "{e.isFunction(n[this.className])&&n[this.className]()})}if(e.event.swipe){this.el.on(\"swipeleft\",n.prev).on(\"swiperight\",n.next)}};this.move=function(t,r)" .
            "{if(!this.items.eq(t).length)t=0;if(t<0)t=this.items.length-1;var i=this.items.eq(t);var s={height:i.outerHeight()};var o=r?5:this.opts.speed;if(!this.ul.is(\":" .
            "animated\")){n.el.find(\".dot:eq(\"+t+\")\").addClass(\"active\").siblings().removeClass(\"active\");this.el.animate(s,o)&&this.ul.animate(e.extend({left:\"-\"+t+\"00%\"},s)" .
            ",o,function(i){n.current=t;e.isFunction(n.opts.complete)&&!r&&n.opts.complete(n.el)})}};this.start=function(){n.interval=setInterval(function(){n.move(n.current+1)},n.opts.delay)}" .
            ";this.stop=function(){n.interval=clearInterval(n.interval);return n};this.keys=function(t){var r=t.which;var i={37:n.prev,39:n.next,27:n.stop};if(e.isFunction(i[r])){i[r]()}};" .
            "this.next=function(){return n.stop().move(n.current+1)};this.prev=function(){return n.stop().move(n.current-1)};this.dots=function(){var t='<ol class=\"dots\">';" .
            "e.each(this.items,function(e){t+='<li class=\"dot'+(e<1?\" active\":\"\")+'\">'+(e+1)+\"</li>\"});t+=\"</ol>\";this.el.addClass(\"has-dots\").append(t).find(\".dot\")." .
            "click(function(){n.move(e(this).index())})}};e.fn.unslider=function(t){var r=this.length;return this.each(function(i){var s=e(this);var u=(new n).init(s,t);" .
            "s.data(\"unslider\"+(r>1?\"-\"+(i+1):\"\"),u)})}})(window.jQuery,false);";

        return $res;
    }


}