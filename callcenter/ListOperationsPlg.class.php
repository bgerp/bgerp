<?php


/**
 * В листовия изглед добавя бутони за различни десйтвия с бутона
 * 
 * @category  bgerp
 * @package   callcenter
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class callcenter_ListOperationsPlg extends core_Plugin
{
    
    
    /**
     * След подготвяне на загалвието на листовия излглед
     * 
     * @param core_Mvc $mvc
     * @param object $res
     * @param object $data
     */
    static function on_AfterPrepareListTitle($mvc, $res, &$data)
    {
        // Полето, което ще се използва за търсене на номер
        $numberField = ($mvc->numberField) ? $mvc->numberField : 'number';
        
        // Ако не се търси по това поле
        if (!($number = $data->listFilter->rec->{$numberField})) return ;
        
        // Ако е коректен номер, според нашите очаквания
        if (!($numberArr = drdata_PhoneType::toArray($number))) return ;
        
        // Вземаме стринга от номер
        $number = drdata_PhoneType::getNumStrFromObj($numberArr[0], '+');
        $number = drdata_PhoneType::escape($number);
        
        // Променяме полето за заглавеи
        $data->title = 'Номер|*: ' . $number;
                
        // Добавяме бутон за избиране
        $data->callLink = ht::createBtn('Избиране', "tel: {$number}", FALSE, FALSE, array('ef_icon' => '/img/16/call.png', 'class' => 'out-btn'));
        
        // Ако има права за изпращане на факс
        if (email_FaxSent::haveRightFor('send')) {
            
            // URL, където да сочи бутона за нов факс
            $urlArr = email_FaxSent::getAddFaxUrl($number);
            $urlArr['ret_url'] = TRUE;
            $data->faxLink = ht::createBtn('Факс', $urlArr, FALSE, FALSE, array('ef_icon' => '/img/16/fax.png'));
        }
        
        // Ако може да се създава SMS
        if (callcenter_SMS::haveRightFor('add')) {
            
            // Бутон за СМС
            $data->smsLink = ht::createBtn('SMS', array('callcenter_SMS', 'add', 'mobileNum' => $number, 'ret_url' => TRUE), FALSE, FALSE, array('ef_icon' => '/img/16/mobile2.png'));
        }
    }
    
    
    /**
     * След рендиране на загалвието на листовия излглед
     * След заглавието добавя и бутоните за различни действия с номера
     * 
     * @param core_Mvc $mvc
     * @param core_Et $tpl
     * @param object $data
     */
    static function on_AfterRenderListTitle($mvc, &$tpl, &$data)
    {
        // Ако няма шаблон
        if (!$tpl) {
            
            // Създаваме шаблон за титлата
            $tpl = new ET("<div class='listTitle'>[#1#]</div>", tr($data->title));
        }
        
        // Шаблон за бутоните
        $buttonTpl = new ET("<div class='listTitleButtons'>[#listTitleParams#]</div>");
        
        // Добавяме бутоните към заглавието
        $buttonTpl->append($data->callLink, 'listTitleParams');
        $buttonTpl->append($data->faxLink, 'listTitleParams');
        $buttonTpl->append($data->smsLink, 'listTitleParams');
        
        // Добавяме към титлата
        $tpl->append($buttonTpl);
    }
}
