<?php


/**
 * Клас 'cat_tpl_PriceListForGroup' -
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
class cat_tpl_PriceListForGroup extends core_ET
{
    /**
     *  @todo Чака за документация...
     */
    public function init($params = array())
    {
        
        $html = "[#SingleToolbar#]
                        <style type='text/css'> 
                           div {display: block; text-align: left;}
                           .clear_l {clear: left;}
                           
                           .a_left {tesx-align: left;}
                              
                           .b {font-weight: bold;}
                           .no_b {font-weight: normal;}
                           .gr {color: #777777;}
                              
                           table.md td {font-size: 12px; padding: 3px 10px; background: #fafafa;}
                           
                           .hgsImage {background: #f0f0f0; 
                                      border: solid 1px #dddddd; 
                                      padding: 3px;
                                      width: 85px;
                                      height: 100px;
                                      margin-right: 0px;
                                      margin-top: 10px;
                                      }
                        </style>
                        
                        <table class='md' cellspacing='0' style='margin-top: 40px; 
                                                          background: #dddddd; 
                                                          border-spacing: 0px;
                                                          border: solid 1px #dddddd;
                                                          margin-bottom: 20px;
                                                          max-width: 700px; 
                                                          -moz-box-shadow: 5px 5px 5px #cccccc;'>
                           <tr>
                              <td>[#id#]</td>
                              <td>[#code#]</td>
                              <td>[#title#]</td>
                              <td>[#price#]</td>
                              <td>[#date#]</td>
                           </tr>
                        </table>";
        
        return parent::core_ET($html);
    }
}