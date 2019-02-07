<?php


/**
 * Клас 'swiper_Adapter'
 *
 * Адаптер за swiper към bgERP
 *
 * @category  bgerp
 * @package   swiper
 *
 * @author    Nevena Georgieva <nevena@experta.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @link     https://www.jssor.com/
 */
class jssor_Adapter extends core_Manager
{

    /**
     * Рендира необходимият HTML за показване на картинките
     */
    public static function renderHtml($images, $options = array())
    {
       // $options = array('arrows' => true);

        // Ако е текстов режим, да не сработва
        if (Mode::is('text', 'plain')) {
            
            return '';
        }
        
        // Ако няма картинки - да не сработва
        if (!is_array($images) || !count($images)) {
            
            return ;
        }
                
        $tpl = new ET("
            <style>
            .holder {
                max-width: 800px;
                height: auto;
                width: 100%;
            }
            .container {
                 position: relative;
                 margin: 0px 5px 5px 0px;
                 float: left;
                 top: 0px;
                 left: 0px;
                 width: 600px;
                 height: 300px;
                 overflow: hidden;
                }
                .slides {
                  position: absolute;
                  left: 0px;
                  top: 0px;
                  width: 600px;
                  height: 300px;
                  overflow: hidden;
                }
            </style>
            <div class='holder'>
            <div id=\"slider1_container\" class='container'>
                <div data-u=\"slides\" class='slides'>
                    [#JSSOR_SLIDES#]
                </div>
                <!--ET_BEGIN SLIDER_ARROWS-->
                [#SLIDER_ARROWS#]
                    <style>
                        .jssora073 {display:block;position:absolute;cursor:pointer;}
                        .jssora073 .a {fill:#ddd;fill-opacity:.7;stroke:#000;stroke-width:160;stroke-miterlimit:10;stroke-opacity:.7;}
                        .jssora073:hover {opacity:.8;}
                        .jssora073.jssora073dn {opacity:.4;}
                        .jssora073.jssora073ds {opacity:.3;pointer-events:none;}
                    </style>
                    <div data-u=\"arrowleft\" class=\"jssora073\" style=\"width:50px;height:50px;top:0px;left:10px;\" data-autocenter=\"2\" data-scale=\"0.75\" data-scale-left=\"0.75\">
                        <svg viewBox=\"0 0 16000 16000\" style=\"position:absolute;top:0;left:0;width:100%;height:100%;\">
                            <path class=\"a\" d=\"M4037.7,8357.3l5891.8,5891.8c100.6,100.6,219.7,150.9,357.3,150.9s256.7-50.3,357.3-150.9 l1318.1-1318.1c100.6-100.6,150.9-219.7,150.9-357.3c0-137.6-50.3-256.7-150.9-357.3L7745.9,8000l4216.4-4216.4 c100.6-100.6,150.9-219.7,150.9-357.3c0-137.6-50.3-256.7-150.9-357.3l-1318.1-1318.1c-100.6-100.6-219.7-150.9-357.3-150.9 s-256.7,50.3-357.3,150.9L4037.7,7642.7c-100.6,100.6-150.9,219.7-150.9,357.3C3886.8,8137.6,3937.1,8256.7,4037.7,8357.3 L4037.7,8357.3z\"></path>
                        </svg>
                    </div>
                    <div data-u=\"arrowright\" class=\"jssora073\" style=\"width:50px;height:50px;top:0px;right:10px;\" data-autocenter=\"2\" data-scale=\"0.75\" data-scale-right=\"0.75\">
                        <svg viewBox=\"0 0 16000 16000\" style=\"position:absolute;top:0;left:0;width:100%;height:100%;\">
                            <path class=\"a\" d=\"M11962.3,8357.3l-5891.8,5891.8c-100.6,100.6-219.7,150.9-357.3,150.9s-256.7-50.3-357.3-150.9 L4037.7,12931c-100.6-100.6-150.9-219.7-150.9-357.3c0-137.6,50.3-256.7,150.9-357.3L8254.1,8000L4037.7,3783.6 c-100.6-100.6-150.9-219.7-150.9-357.3c0-137.6,50.3-256.7,150.9-357.3l1318.1-1318.1c100.6-100.6,219.7-150.9,357.3-150.9 s256.7,50.3,357.3,150.9l5891.8,5891.8c100.6,100.6,150.9,219.7,150.9,357.3C12113.2,8137.6,12062.9,8256.7,11962.3,8357.3 L11962.3,8357.3z\"></path>
                        </svg>
                    </div>
                <!--ET_END SLIDER_ARROWS-->
            </div>
        </div>
        ");


       foreach($images as $url) {
           $slide = "\n    <div><img data-u=\"image\" src='{$url}' /></div>";
           $tpl->append($slide, 'JSSOR_SLIDES');
       }
        
        // Да има ли бутони за навигация?
        if($options['arrows']) {
            $tpl->append(' ', 'SLIDER_ARROWS');
        }

        // Вземаме актуалната версия
        $ver = jssor_Setup::get('VERSION');

        // Включваме необходимия JS
        $tpl->push("jssor/{$ver}/js/jssor.slider.min.js", 'JS');
        $tpl->push("jssor/script.js", 'JS');


        $tpl->removeBlocks();

        return $tpl;
    }


    /**
     * Метод за тестване
     */
    public function act_Test()
    {   
 
        $tpl = self::renderHtml(array(
            'http://lorempixel.com/800/400/nature/1/',
            'http://lorempixel.com/800/400/nature/2/',
            'http://lorempixel.com/800/400/nature/3/',
            'http://lorempixel.com/800/400/nature/4/',
            'http://lorempixel.com/800/400/nature/5/',));

       return $tpl;

    }
}
