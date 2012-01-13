<?php



/**
 * Клас 'sales_tpl_ViewSingleLayoutInvoice' -
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class sales_tpl_ViewSingleLayoutInvoice extends core_ET
{
    
    
    /**
     * Инициализиране на обекта
     */
    public function init($params = array())
    {
        // bp($params['data']);
        
        $htmlInvoice = "
<table width=\"100%\" style=\"page-break-before: always;\" >
    <tbody>
    <tr>
        <td></td>
    </tr>

    <tr>
        <td style=\"padding-top: 10px;\">
            <style type=\"text/css\"> 
                .smallData {padding:1px; font-size:1em; }
                .bigData {font-family:\"Courier New\"; font-size:1em; font-weight:bold;}
                .cell {border:1px solid    #000;}
                .topCell { border-top:none; border-left: 1px solid #000; border-bottom: 1px solid #000; border-right: 1px solid #000;} 
                .invTable { border-collapse:collapse; background-color:white;  }
                .rowData {font-size:0.9em;}
            </style>

            <form action=\"\" method=\"post\">
            <table class=\"invTable\" border=\"0\" cellpadding=\"1\" cellspacing=\"0\" width=\"100%\">
                <tbody><tr>
                    <td class=\"cell\" width=\"35%\"><b>Получател / <i>Buyer</i></b></td>
                    <td class=\"cell\" rowspan=\"3\" align=\"center\" width=\"30%\">
                        <font style=\"font-size:16pt;\"><b> ФАКТУРА / <i>INVOICE</i> </b> </font><br>
                        
                        <div style=\"padding-top:5px;\">Номер / Number</div>
                        <div class=\"bigData\">[#number#]</div>
                        
                        <div style=\"padding-top:5px;\">Дата / Date</div>
                        <div class=\"bigData\">[#date#]</div>
    
                        <div style=\"padding-top:5px;\">Място / Place</div>
                        <div class=\"bigData\">[#dealPlace#]</div>

                        <div style=\"padding-top:5px;\" class=\"bigData\"><font color=\"999999\"><b>ОРИГИНАЛ/<i>ORIGINAL</i></b></font></div>
                    </td>
                    <td class=\"cell\" width=\"35%\"><b>Доставчик / <i>Seller</i></b> </td>
                </tr>           
                <tr>
                    <td class=\"cell\">
                        <b>[#contragentName#]</b><br>
                        <b>[#contragentCountry#]</b><br>
                        [#contragentAddress#]<br>
                    </td>

                    <td class=\"cell\">
                        <b>ЕКСТРАПАК ООД</b><br>
                        <b>България</b><br>
                        В. Търново, Западна Промишлена Зона<br>
                    </td>
                </tr>
                <tr>
                    <td class=\"cell\">
                        <table cellpadding=\"0\" cellspacing=\"2\">
                            <tbody><tr>
                                <td><small><b>номер по ЗДДС / <i>VAT ID</i></b></small></td>
                            </tr>
                            <tr>
                                <td class=\"bigData\">[#contragentVatId#]</td>
                            </tr>
                            <tr>
                                <td><small><b>Идентификационен номер</b></small></td>
                            </tr>
                            <tr>
                                <td class=\"bigData\">[#contragentId#]</td>
                            </tr>
                            </tbody>
                        </table>
                    </td>

                    <td class=\"cell\">
                        <table>
                            <tbody><tr>
                                <td><small><b>номер по ЗДДС / <i>VAT ID</i></b></small></td>
                            </tr>
                            <tr>
                                <td class=\"bigData\">BG 814228908</td>
                            </tr>
                            <tr>
                                <td><small><b>Идентификационен номер</b></small></td>
                            </tr>
                            <tr>
                                <td class=\"bigData\">814228908</td>
                            </tr>
                            </tbody>
                        </table>            
                    </td>
                </tr>
                </tbody>
            </table>

            [#Detailacc_InvoiceDetails#]
            
            <table class=\"invTable\" border=\"0\" cellpadding=\"5\" cellspacing=\"0\" width=\"100%\">
                <tbody><tr>
                    <td class=\"topCell\" valign=\"top\" width=\"30%\">
                        <small><br><b>Отговорен за стопанската операция:</b><br><br>
                        ...........................................................
                        <br></small><br><hr>
                        <!-- <style src=\"http://printed-bags.net/inc/richtext.css\"></style> -->
                            
                        <style type=\"text/css\">
                        <!--
                            @import url(\"http://printed-bags.net/inc/richtext.css\");
                        -->
                        </style>
                            
                        <span class=\"richtext\">15193206</span>      
                    </td>
                    <td class=\"topCell\" valign=\"top\" width=\"40%\">
                        <div style=\"font-size:8pt;\">Плащане / <i>Payment</i><div>
                        <div class=\"smallData\">Плащане 35 дни след датата на данъчното събитие</div>
                        <div class=\"smallData\">Краен срок за плащане: <b>17/08/2011</b></div>
                        <div class=\"smallData\">Лихва за просрочено плащане <b>0.05%</b> на ден</div>
                        <div style=\"font-size:8pt;\"><hr>Банкова с-ка / <i>Bank account</i><div>
                        <div class=\"smallData\"><b>Уникредит Булбанк (BGN)</b></div>
                        <div class=\"smallData\">IBAN  <b>BG14UNCR96601020078812</b></div>
                        <div class=\"smallData\">BIC(SWIFT)  <b>UNCRBGSF</b></div>
                        </div></div></div></div>
                    </td>
       
                    <td class=\"topCell\" align=\"right\" valign=\"bottom\" width=\"30%\">
                        <table>
                            <tbody><tr>
                                <td style=\"padding-top: 10px; text-align: right;\">
                                    <small><b>Съставил:</b>..................................&nbsp;
                                    <br>
                                    </small>
                                </td>
                            </tr>
                            <tr>
                                <td align=\"RIGHT\">Данчи Димова</td>
                            </tr>
                            <tr>
                                <td style=\"padding-top: 10px; text-align: right;\"><small><b>Дата на даннъчното събитие</b></small></td>
                            </tr>
                            <tr>
                                <td align=\"RIGHT\">12/07/2011</td>
                            </tr>
                            <tr>
                                <td style=\"padding-top:10px; text-align:right;\"><small><b>Основание за размера на ДДС</b></small></td>
                            </tr>
                            <tr>
                                <td align=\"RIGHT\"></td>
                            </tr>
                            </tbody>
                        </table>
                          
                        <div style=\"padding:10px;\">
                            <div style=\"padding-top:5px;\">Към продажба / <i>To sale</i></div>
                            <div class=\"bigData\"><a href=\"http://printed-bags.net/crm/do2.php?ctr=Sales&amp;act=detail&amp;id=94460\"> <b>№94460 от 27/06/2011</b></a> </div>
                        </div>
                    </td>
                </tr>           
                </tbody>
            </table>
            </form>

        </td>
    </tr>
    <tr>
        <td></td>
    </tr>
    </tbody>
</table>";
        
        return parent::core_ET($htmlInvoice);
    }
}