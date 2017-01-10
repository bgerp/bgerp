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
            
            // Бутон за SMS
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
     * 
     * @param array $numbersArr
     * 
     * @return string
     */
    private static function getSearchQueryLink($numbersArr)
    {
        $numberObj = $numbersArr[0];
        $countryCode = $numberObj->countryCode;
        $areaCode    = $numberObj->areaCode;
        $number = $numberObj->number;
        
        if($areaCode == '87' || $areaCode == '88' | $areaCode == '89') {
            $areaCode .= $number{0};
            $number = substr($number, 1);
        }

        $numArr = self::parseNumber("{$number}");
        $sNum = "0{$areaCode}_". implode('_', $numArr);
        
        $tel = ($countryCode == 359) ? 'тел' : 'tel';
        
        $q = "{$countryCode}{$areaCode}{$number} | $tel {$sNum}";
        
        if(strlen($number) == 6) {
            $q .= " | 0{$areaCode}_" . substr($number, 0, 2) . '_' . substr($number, 2, 2) . '_' . substr($number, 4, 2);
        }

        $q = urlencode($q);
        $url = "https://www.google.bg/search?q={$q}";
        
        return $url;
    }
    
    
    /**
     * Връща номера подходящ за търсене
     * 
     * @param string $n
     * 
     * @return string
     */
    protected static function parseNumber($n)
    {
        $len = strlen($n);
        
        $r = (int) ($len % 3);
        $d = (int) ($len / 3);
        
        $nArr = array();
        
        // Ако дължината на номера е кратно на 3
        if (($r === 0) && ($d !== 0)) {
            $nArr = str_split($n, 3);
        } elseif ($d === 0) {
            // Ако е под 3 символа
            $nArr[] = $n;
        } elseif ($r === 1) {
            // Когото не е кратен на 3, но има остатък 1 - примерно 4,7
            
            // Когато е 4 символа
            if ($d === 1) {
                $nArr = str_split($n, 2);
            } else {
                // Вземаме първите три символа и пак парсираме номера
                $nArr[] = substr($n, 0, 3);
                $n = substr($n, 3);
                
                $nArr = array_merge($nArr, self::parseNumber($n));
            }
        } elseif ($r === 2) {
            // Когото не е кратен на 3, но има остатък 2 - примерно 5
            $nArr[] = substr($n, 0, 2);
            $n = substr($n, 2);
            $nArr = array_merge($nArr, self::parseNumber($n));
        } else {
            $nArr[] = $n;
        }
        
        return $nArr;
    }
}
