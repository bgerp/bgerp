<?php


/**
 * Клас 'swiper_Adapter'
 *
 * Адаптер за swiper към bgERP
 *
 * @category  bgerp
 * @package   swiper
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @link      http://idangero.us/swiper/
 */
class swiper_Adapter extends core_Manager
{

    /**
     * Рендира необходимият HTML за показване на картинките
     */
    public static function renderHtml($images, $options = array())
    {
       // $options = array('thums' => true);
        $options = array('arrows' => true);

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
                .swiper-container {
                  max-width: 880px;
                  width: 100%;
                  height: 300px;
                  margin-left: auto;
                  margin-right: auto;
                }
                .swiper-slide {
                  background-size: cover;
                  background-position: center;
                }
            </style>

            <div class=\"swiper-container gallery-top\">
                <div class=\"swiper-wrapper\">
                    [#SWIPER_SLIDES#]
                </div>
                <!--ET_BEGIN SWIPPER_ARROWS-->
                    [#SWIPPER_ARROWS#]
                    <div class=\"swiper-button-next swiper-button-white\"></div>
                    <div class=\"swiper-button-prev swiper-button-white\"></div>
                <!--ET_END SWIPPER_ARROWS-->
            </div>
            <!--ET_BEGIN SWIPER_THUMBS-->
            <div class=\"swiper-container gallery-thumbs\">
                <div class=\"swiper-wrapper\">
                 [#SWIPER_THUMBS#]
                </div>
            </div>
            <!--ET_END SWIPER_THUMBS-->

        ");


       foreach($images as $url) {
           $slide = "\n   <div class=\"swiper-slide\" style=\"background-image:url({$url})\"></div>";
           $tpl->append($slide, 'SWIPER_SLIDES');
           if($options['thumbs']) {
               $thumb = "\n   <div class=\"swiper-slide\" style=\"background-image:url({$url})\"></div>";
               $tpl->append($thumb, 'SWIPER_THUMBS');
           }
       }
        
        // Да има ли бутони за навигация?
        if($options['arrows']) {
            $tpl->append(' ', 'SWIPPER_ARROWS');
        }

        // Вземаме актуалната версия
        $ver = swiper_Setup::get('VERSION');

        // Включваме необходимия JS
        $tpl->push("swiper/{$ver}/js/swiper.min.js", 'JS');
        $tpl->push("swiper/script.js", 'JS');

        // Включваме необходимия CSS
        $tpl->push("swiper/{$ver}/css/swiper.min.css", 'CSS');

        // Зареждаме контейнера с thumnails
        if($options['thumbs']) {
            jquery_Jquery::run($tpl, 'prepareThumbsSwiper();');
        }

        // Стартираме swiper
        jquery_Jquery::run($tpl, 'startSwiper();');
        
        $tpl->removeBlocks();

        return $tpl;
    }


    /**
     * Метод за тестване
     */
    public function act_Test()
    {   
 
        $tpl = self::renderHtml(array(
            'http://lorempixel.com/1200/1200/nature/1/',
            'http://lorempixel.com/1200/1200/nature/2/',
            'http://lorempixel.com/1200/1200/nature/3/',
            'http://lorempixel.com/1200/1200/nature/4/',
            'http://lorempixel.com/1200/1200/nature/5/',));

       return $tpl;

    }
}
