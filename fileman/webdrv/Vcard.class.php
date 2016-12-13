<?php


/**
 * Драйвер за работа с vCard файлове.
 * 
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_webdrv_Vcard extends fileman_webdrv_Generic
{
    
    
    /**
     * Кой таб да е избран по подразбиране
     * @Override
     * @see fileman_webdrv_Generic::$defaultTab
     */
    static $defaultTab = 'preview';
    
    
    /**
     * Връща всички табове, които ги има за съответния файл
     * 
     * @param object $fRec - Записите за файла
     * 
     * @return array
     * 
     * @Override
     * @see fileman_webdrv_Generic::getTabs
     */
    static function getTabs($fRec)
    {
        // Вземаме табовете от родителя
        $tabsArr = parent::getTabs($fRec);
        
        // Преглед
        $previewStr = static::getPreview($fRec);
        
        // Вземаме съдържанието
        $contentStr = static::getContent($fRec, TRUE);
        
        // Инстанция на класа
        $richText = cls::get('type_Richtext');
        
        // Преобразуваме файла в код
        $contentStr = $richText->toHtml("[code]{$contentStr}[/code]");
        
        // Таб за преглед
		$tabsArr['preview'] = (object) 
			array(
				'title'   => 'Преглед',
				'html'    => "<div class='webdrvTabBody' style='white-space:pre-wrap;'><div class='webdrvFieldset'><div class='legend'>" . tr("Преглед") . "</div>{$previewStr}</div></div>",
				'order' => 2,
			);
			
		// Таб за съдържанието
		$tabsArr['content'] = (object) 
			array(
				'title'   => 'Съдържание',
				'html'    => "<div class='webdrvTabBody' style='white-space:pre-wrap;'><div class='webdrvFieldset'><div class='legend'>" . tr("Съдържание") . "</div>{$contentStr}</div></div>",
				'order' => 7,
			);
        
        return $tabsArr;
    }
    
    
	/**
	 * Връща текстовата част
     * 
     * @param object $fRec - Запис на визитката
     * @param boolean $escape - Дали да се ескейпва текстовата част
     * 
     * @return string $content - Текстовата част
     */
    static function getContent($fRec, $escape=TRUE) 
    {
        // Вземаме тексстовата част
        $content = fileman_Files::getContent($fRec->fileHnd);
        
        // Ако е зададено да се ескейпват
        if ($escape) {
            
            // Ескейпваме текстовата част
            $content = type_Varchar::escape($content);    
        }

        return $content;
    }
    
    
    /**
     * Връща вербалния вид на визитките
     * 
     * @param object $fRec - Запис на визитката
     * 
     * @return string
     */
    static function getPreview($fRec) 
    {
        // Масив с всички визитки
        $dataArr = static::prepareData($fRec);
        
        // Текстовата част на визитките
        $content = static::prepareContent($dataArr);
        
        // Връщаме изгледа
        return $content;
    }
    
    
    /**
     * Подготвяме данните за мисава
     * 
     * @param object $fRec - Запис на визитката
     * 
     * @return array - Масив с всики визитки и техните стойности
     */
	static function prepareData($fRec)
	{
	    // Масив, в който ще се записват съдържанието на визитките
	    $dataArr = array();
	    
	    // Вземаме текстовата част
        $content = static::getContent($fRec, FALSE);
        
        // Зареждаме визитките
        $vcards = pear_Vcard::parseString($content);
        
        // Брояч на масива
        $i=0;
        
        // Обхождаме всички визитки
        foreach ((array)$vcards as $vcard) {
            $dataArr[$i]['version'] = $vcard->getVersion(); // string
            $dataArr[$i]['revision'] = $vcard->getRevision(); // int Unix TimeStamp
            $dataArr[$i]['formattedName'] = $vcard->getFormattedName(); // fullName string
            $dataArr[$i]['name'] = $vcard->getName(); //array - surname, given, additional, prefix, suffix (string)
            $dataArr[$i]['photoUrl'] = $vcard->getPhotoUrl(); // array - key => link
            $dataArr[$i]['bDay'] = $vcard->getBday('d-m-Y'); // string dd-mm-YYYY
            $dataArr[$i]['tel'] = $vcard->getTel(); // array - work, voice, home, fax, cell, pref (array)
            $dataArr[$i]['Emails'] = $vcard->getEmails(); // array - pref, internet, 0... (array)
            $dataArr[$i]['Address'] = $vcard->getAddress(); // array - work, home, intl, parcel, dom, 0... (array)
            $dataArr[$i]['addressLabel'] = $vcard->getAddressLabel(); // array - work, home (array)
            $dataArr[$i]['organization'] = $vcard->getOrganisation(); // string
            $dataArr[$i]['jobTitle'] = $vcard->getJobTitle(); // string
            $dataArr[$i]['role'] = $vcard->getRole(); // string
            
            // Увеличаваме с единица
            $i++;
        }
        
        return $dataArr;
	}
	
	
	/**
	 * Преобразува масива в шаблон за показване
	 * 
	 * @param array $dataArr - Масив с всики визитки и техните стойности
	 * 
	 * @return core_ET $content - Шаблон със съдържанието
	 */
	static function prepareContent($dataArr)
	{
	    
	    // Шаблона на визитките
	    $content = new ET("[#content#]");
	    
	    // Флаг указващ, че обхождаме първия елемент на масива
        $first = TRUE;
        
        // Обхождаме масива
	    foreach ((array)$dataArr as $vcardArr) {
	        
	        // Шаблона, в който ще заместваме данните от визитката
	        $tpl = new ET(tr('|*'.getFileContent('fileman/tpl/VcardDriverLayout.txt') . '|'));
	        
	        // Заместваме имената
	        $tpl->replace($vcardArr['formattedName'], 'formattedName');
	        
	        // Заместваме имената детайлно
	        $tpl->replace($vcardArr['name']['given'], 'given');
	        $tpl->replace($vcardArr['name']['surname'], 'surname');
	        $tpl->replace($vcardArr['name']['additional'], 'additional');
	        $tpl->replace($vcardArr['name']['prefix'], 'prefix');
	        $tpl->replace($vcardArr['name']['suffix'], 'suffix');
	        
	        // Заместваме версията на vCard
	        $tpl->replace($vcardArr['version'], 'version');
	        
	        // Заместваме версияна на визитката
	        if ($vcardArr['revision']) {
	            
	            // Вземаме датата във вербална форма
	            $revision = dt::mysql2verbal(dt::timestamp2Mysql($vcardArr['revision']), 'smartTime');
	            
	            // Заместваме
	            $tpl->replace($revision, 'revision');
	        }
	        
	        // Заместваме рожденния ден
	        $tpl->replace($vcardArr['bDay'], 'bDay');
	        
	        // Заместваме името на предпирятите
	        $tpl->replace($vcardArr['organization'], 'organization');
	        // Заместваме длъжността
	        $tpl->replace($vcardArr['jobTitle'], 'jobTitle');
	        // Заместваме ролята
	        $tpl->replace($vcardArr['role'], 'role');
	        
	        // Флаг указващ, че обхождаме първия елемент на масива
	        $firstPhoto = TRUE;
	        // Обхождаме всички снимки
	        foreach ((array)$vcardArr['photoUrl'] as $photoUrl) {
	            
	            // Проверка дали за първи път влизаме
	            (!$firstPhoto) ? ($photoUrl = ", " . $photoUrl) :  ($firstPhoto = FALSE);
	            
	            // Добавяме към шаблона
	            $tpl->append($photoUrl, 'photoUrl');    
	        }
	        
	        // Инстанция на класа
	        $Email = cls::get('type_Email');
	        
	        // Обхождаме всички имейли в масива
	        foreach ((array)$vcardArr['Emails'] as $type => $emailsArr) {
	            
	            // Флагове указващи, че обхождаме първия елемент от съответния подмасив
	            $firstPref = TRUE;
	            $firstInt = TRUE;
	            $firstOther = TRUE;
	            // Обхождаме всички имейли
	            foreach ((array)$emailsArr as $email) {
	                
	                // Вземаме вербалния имейл
	                $email = $Email->toVerbal($email);
	                
	                // В зависимост от типа
	                switch (strtolower($type)) {
	                    
	                    case 'internet':
                            
	                        // Проверка дали за първи път влизаме
	                        (!$firstInt) ? ($email = ", " . $email) :  ($firstInt = FALSE);
    	                
	                        // Добавяме към шаблона
    	                    $tpl->append($email, 'internetEmails'); 
	                    break;
	                    
	                    case 'pref':
	                        
	                        // Проверка дали за първи път влизаме
	                        (!$firstPref) ? ($email = ", " . $email) :  ($firstPref = FALSE);
    	                
	                        // Добавяме към шаблона
    	                    $tpl->append($email, 'prefEmail');
	                    break;
	                    
	                    
	                    default:
	                        
	                        // Проверка дали за първи път влизаме
	                        (!$firstOther) ? ($email = ", " . $email) :  ($firstOther = FALSE);
    	                
	                        // Добавяме към шаблона
    	                    $tpl->append($email, 'otherEmails');
	                    break;
	                }
	            }
	        }
	        
	        // Инстанция на класа
	        $Phone = cls::get('drdata_PhoneType');
	        
	        // Обхождаме всички телефони в масива
	        foreach ((array)$vcardArr['tel'] as $type => $phonesArr) {
	            
	            // Флагове указващи, че обхождаме първия елемент от съответния масив
	            $firstPref = TRUE;
	            $firstCell = TRUE;
	            $firstWork = TRUE;
	            $firstHome = TRUE;
	            $firstVoice = TRUE;
	            $firstFax = TRUE;
	            $firstOther = TRUE;
	            // Обхождаме всички телефони
	            foreach ((array)$phonesArr as $phone) {
	                
	                // Вземаме вербалния телефон
	                $phone = $Phone->toVerbal($phone);
	                
	                // В зависимост от типа
	                switch (strtolower($type)) {
	                    
	                    case 'pref':
	                        
	                        // Проверка дали за първи път влизаме
                            (!$firstPref) ? ($phone = ", " . $phone) :  ($firstPref = FALSE);
    	                
                            // Добавяме към шаблона
    	                    $tpl->append($phone, 'prefPhones');
	                    break;
	                    
	                    case 'cell':
	                        
	                        // Проверка дали за първи път влизаме
	                        (!$firstCell) ? ($phone = ", " . $phone) :  ($firstCell = FALSE);
    	                
	                        // Добавяме към шаблона
    	                    $tpl->append($phone, 'cellPhones');
	                    break;
	                    
	                    case 'work':
	                        
	                        // Проверка дали за първи път влизаме
	                        (!$firstWork) ? ($phone = ", " . $phone) :  ($firstWork = FALSE);
    	                
	                        // Добавяме към шаблона
    	                    $tpl->append($phone, 'workPhones');
	                    break;
	                    
	                    case 'home':
	                        
	                        // Проверка дали за първи път влизаме
	                        (!$firstHome) ? ($phone = ", " . $phone) :  ($firstHome = FALSE);
    	                
	                        // Добавяме към шаблона
    	                    $tpl->append($phone, 'homePhones');
	                    break;
	                    
	                    case 'voice':
	                        
	                        // Проверка дали за първи път влизаме
	                        (!$firstVoice) ? ($phone = ", " . $phone) :  ($firstVoice = FALSE);
    	                
	                        // Добавяме към шаблона
    	                    $tpl->append($phone, 'voicePhones');
	                    break;
	                    
	                    case 'fax':
	                        
	                        // Проверка дали за първи път влизаме
	                        (!$firstFax) ? ($phone = ", " . $phone) :  ($firstFax = FALSE);
    	                
	                        // Добавяме към шаблона
    	                    $tpl->append($phone, 'faxPhones');
	                    break;
	                    
	                    default:
	                        
	                        // Проверка дали за първи път влизаме
	                        (!$firstOther) ? ($phone = ", " . $phone) :  ($firstOther = FALSE);
    	                
	                        // Добавяме към шаблона
    	                    $tpl->append($phone, 'otherPhones');
	                    break;
	                }
	            }
	        }
	        
	        // Обхождаме всички адреси в масива
	        foreach ((array)$vcardArr['addressLabel'] as $type => $addressArr) {
	            
	            // Флагове указващи, че обхождаме първия елемент от съответния масив
	            $firstHome = TRUE;
	            $firstWork = TRUE;
	            $firstOther = TRUE;
	            // Обхождаме всички телефони
	            foreach ((array)$addressArr as $address) {
	                
	                // Обработваме адреса
	                $address = bglocal_Address::canonizePlace($address);
	                
	                // В зависимост от типа
	                switch (strtolower($type)) {
	                    case 'home':
	                        
	                        // Заместваме всеки нов ред с нов ред и табулация
	                        $address = str_ireplace("\n", "\n\t", $address);
	                        
	                        // Проверка дали за първи път влизаме
	                        (!$firstHome) ? ($address = "\n" . $address) :  ($firstHome = FALSE);
	                        
	                        // Добавяме към шаблона
	                        $tpl->append($address, 'homeAddress');
	                    break;
	                    
	                    case 'work':
	                        
	                        // Заместваме всеки нов ред с нов ред и табулация
	                        $address = str_ireplace("\n", "\n\t", $address);

	                        (!$firstWork) ? ($address = "\n" . $address) :  ($firstWork = FALSE);
	                        
	                        // Добавяме към шаблона
	                        $tpl->append($address, 'workAddress');
	                    break;
	                    
	                    default:
	                        
	                        // Заместваме всеки нов ред с нов ред и табулация
	                        $address = str_ireplace("\n", "\n\t", $address);
	                        
	                        // Проверка дали за първи път влизаме
	                        (!$firstOther) ? ($address = ", " . $address) :  ($firstOther = FALSE);
	                        
	                        // Добавяме към шаблона
	                        $tpl->append($address, 'otherAddress');
	                    break;
	                }
	            }
	        }
	        
	        // Ако не сме за първи път в масива, тогава в началото на шаблона добавяме разделите
	        (!$first) ? ($tpl->replace(static::getSeparator(), 'separator')) :  ($first = FALSE);
	        
	        // Премахваме празните блокове
	        $tpl->removeBlocks();
	        
	        // Заместваме шаблона
	        $content->append($tpl, 'content');
	    }
	    
	    return $content;
	}
	
	
	/**
	 * Връща разделителя за визитките
	 * 
	 * @param int $times - Колко пъти да конкатенира разделителя
	 * @param string $separator - Кое ще се конкатенира
	 * 
	 * @return string $str - Разделителя
	 */
	static function getSeparator($times=100, $separator='-') 
	{
	    // Създаме стринга
	    $str = '';
	    
	    // Очакваме разделителя да е по голям от 0
	    expect($times>0);
	    
	    // Създаваме разделитея
	    for($i=0; $i<$times; $i++) {
	        $str .= $separator;     
	    }
	    
	    return $str;
	}
    
    
	/**
     * Извлича текстовата част от файла
     * 
     * @param object $fRec - Записите за файла
     */
    static function extractText($fRec)
    {
        fileman_webdrv_Text::extractText($fRec);
    }
}
