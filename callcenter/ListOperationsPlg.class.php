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
        $numberDial = drdata_PhoneType::getNumStrFromObj($numberArr[0], '00');
        $numberShow = drdata_PhoneType::getNumStrFromObj($numberArr[0], '+');

        $numberShow = drdata_PhoneType::escape($numberShow) . " <small style='color:#666;'>({$numberArr[0]->country}/{$numberArr[0]->area})</small>";
        
        // Променяме полето за заглавеи
        $data->title = "|*<small style='color:#666;'>|Номер|*:</small> " . $numberShow;
                
        // Добавяме бутон за избиране
        $data->callLink = ht::createBtn('Избиране', "tel: {$numberDial}", FALSE, FALSE, 'ef_icon=/img/16/call.png,class=out-btn,title=Набиране на този номер');
        
        // Преобразува номера в линк за търсене
        $searchLink = self::getSearchQueryLink($numberArr);
        $data->searchLink = ht::createBtn('Кой е?', $searchLink, FALSE, '_blank', 'ef_icon=/img/16/google-search-icon.png,title=Търсене в Google за този номер');
        
        // Ако има права за изпращане на факс
        if (email_FaxSent::haveRightFor('send')) {
            
            // URL, където да сочи бутона за нов факс
            $urlArr = email_FaxSent::getAddFaxUrl($numberDial);
            $urlArr['ret_url'] = TRUE;
            $data->faxLink = ht::createBtn('Факс', $urlArr, FALSE, FALSE, 'ef_icon=/img/16/fax.png,title=Създаване на факс към този номер');
        }
        
        // Ако може да се създава SMS
        if (callcenter_SMS::haveRightFor('send')) {
            
            // Бутон за СМС
            $data->smsLink = ht::createBtn('SMS', array('callcenter_SMS', 'send', 'mobileNum' => $numberDial, 'ret_url' => TRUE), FALSE, FALSE, array('ef_icon' => '/img/16/mobile2.png'));
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
        $buttonTpl->append($data->searchLink, 'listTitleParams');
        $buttonTpl->append($data->faxLink, 'listTitleParams');
        $buttonTpl->append($data->smsLink, 'listTitleParams');
        
        // Добавяме към титлата
        $tpl->append($buttonTpl);
    }
    

    /**
     * Подготвя URL за търсене на телефонен номер
     */
    private static function getSearchQueryLink($numbersArr)
    {
        $numberObj = $numbersArr[0];
        $countryCode = $numberObj->countryCode;
        $areaCode    = $numberObj->areaCode;
        $n = $numberObj->number;

        $nArr[] = $n;
        $res = array();
        switch(strlen($n)) {
            case 4:
                $nArr[] = $n{0} . $n{1} . '_' . $n{2} . $n{3};
                break;
            case 5:
                $nArr[] = $n{0} . '_' . $n{1} . $n{2} . '_' . $n{3} . $n{4};
                $nArr[] = $n{0} . $n{1} . $n{2} . '_' . $n{3} . $n{4};
                break;
            case 6:
                $nArr[] = $n{0} . $n{1} . '_' . $n{2} . $n{3} . '_' . $n{4} . $n{5};
                $nArr[] = $n{0} . $n{1} . $n{2} . '_' . $n{3} . $n{4} . $n{5};
                break;
            case 7:
                $nArr[] = $n{0} . $n{1} . $n{2} . $n{3} . '_' . $n{4} . $n{5} . $n{6};
                $nArr[] = $n{0} . $n{1} . $n{2} . '_' . $n{3} . $n{4} . '_' . $n{5} . $n{6};
                break;
            case 8:
                $nArr[] = $n{0} . $n{1} . '_'.  $n{2} . $n{3} . '_' . $n{4} . $n{5} . '_' . $n{6} . $n{7};
                $nArr[] = $n{0} . $n{1} . $n{2} .'_' .  $n{3} . $n{4} . $n{5} . '_' . $n{6} . $n{7};
                break;
        }

        foreach($nArr as $number) {
            $variants["00{$countryCode}_{$areaCode}_{$number}"] = TRUE;
            $variants["{$countryCode}_{$areaCode}_{$number}"] = TRUE;
            $variants["0{$areaCode}_{$number}"] = TRUE;
            $variants["{$countryCode}_{$areaCode}_{$number}"] = TRUE;
        }
        
        $cnt = 1;
        foreach($variants as $v => $true) {
            $q .= ($q ? ' OR ' : '') . $v;
            if($cnt++ > 32) break;
        }

        $url = "https://www.google.bg/search?q={$q}";

        return $url;
    }
}