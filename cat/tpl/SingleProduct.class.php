<?php


/**
 * Клас 'cat_tpl_SingleProduct' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    cat
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class cat_tpl_SingleProduct extends core_ET
{
    /**
     *  @todo Чака за документация...
     */
    public function init($params = array())
    {
        
        $html = "<!-- BEGIN container -->
                 <div class='css-lib products-single'>
                    
                     <!-- BEGIN Заглавие -->
                    <div class='clear_l f_left block_title'>
                       <div class='f_left b'>[#SingleTitle#]</div>
                       <div class='f_right'>
                          <span>[#createdOn#]</span>
                      </div>
                    </div>
                    <!-- END Заглавие -->
                   
                    <!-- BEGIN Блок ляво -->
                    <div class='block_left'>
                       
                       <!--ET_BEGIN image1-->
                       <div class='clear_l'> 
                           [#image1#]
                       </div>
                       <!--ET_END image1-->
                         
                       <!--ET_BEGIN image2-->
                       <div class='clear_l'>
                           [#image2#]
                       </div>   
                       <!--ET_END image2-->
                                                              
                       <!--ET_BEGIN image3-->
                       <div class='clear_l'>
                           [#image3#]
                       </div>   
                       <!--ET_END image3-->

                       <!--ET_BEGIN image4-->
                       <div class='clear_l'>
                           [#image4#]
                       </div>   
                       <!--ET_END image4-->

                       <!--ET_BEGIN image5-->
                       <div class='clear_l'>
                           [#image5#]
                       </div>   
                       <!--ET_END image5-->                       
                    </div>
                    <!-- END Блок ляво -->   
                       
                    <!-- BEGIN Блок дясно -->
                    <div class='block_right'>

                        <div class='description'>
                            [#description#]
                        </div>                       
                        <div class='details-tpl'>
                            [#detailsTpl#]
                        </div>                       
                    </div>
                    <!-- END Блок дясно -->                   
                 </div>
                 <!-- END container -->
                 
                 <div style='clear:both;'></div>

                [#SingleToolbar#]";
        
        return parent::core_ET($html);
    }
}