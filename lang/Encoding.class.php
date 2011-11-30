<?php

/**
 * Клас 'lang_Encoding' - Откриване на енкодинга и езика на текст
 *
 * Библиотека с функции за откриване на енкодинга и езика на стринг
 *
 * @category   Experta Framework
 * @package    lang
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2011 Experta OOD
 * @since      v 0.1
 */
class lang_Encoding {
    
    static $lgAnalyzer;

    static $commonCharsets = array(
        'CP1251',
        'UTF-8',
        'ISO-8859-1',
        'US-ASCII',
        'EUC-CN',
        'CP1252',
        'ISO-8859-2',
        'KOI8-R',
        'ISO-8859-15',
        'BIG5',
        'CP1250',
        'ISO-8859-5',
        'ISO-8859-7',
        'ISO-8859-9',
        'GBK => 5',
        'EUC-KR',
        'CP1257',
        'ISO-2022-JP',
        'ISO-8859-3',
        'ISO-2022-KR',
        'CP874',
        'CP1253',
        'ISO-8859-13',
        'CP1256',
        'CP1254',
        'UTF-7',
        'CP1258',
        'JIS_C6220-1969-RO',
        'MACROMAN', 
        );


    /**
     *  Mасив с ключове - алиаси на чарсетове и стойности - официални имена на чарсетове
     */
    static $charsetsMatchs = array();


    /**
     *  Mасив с ключове - алиаси на  и стойности - официални имена на кодировки за двоични данни
     */
    static $encodingsMatchs = array();


    /**
     * Определя каква е потенциално знаковата кодировка на даден текст
     * В допълнение връща и предполагаемия език
     */
    function analyzeCharsets($text )
    {
        $maxLgRate = 0;
        $downCharsetCnt = 10;
        foreach(self::$commonCharsets as $charset) {
            $convText = iconv($charset, 'UTF-8//IGNORE', $text);
            $lgRates = self::getLgRates($convText);
            if(count($lgRates)) { 
                $firstLg = arr::getMaxValueKey($lgRates);
                $firstLgRate = $lgRates[$firstLg] + count($lgRates) + $downCharsetCnt;
                if($firstLg == 'en') $firstLgRate = $firstLgRate * 0.9;
                if($firstLg == 'bg') $firstLgRate = $firstLgRate * 1.1;
                $res->rates[$charset] = $firstLgRate;
                $res->langs[$charset] = $firstLg;

             }
            $downCharsetCnt--;
        }

        return $res;
    }
    

    /**
     * Резултат - ascci, 8bit-non-latin, 8bit-latin, utf8
     */
    function getPossibleEncodings($text)
    {
        $encodings = array('BASE64' => "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=\n\r\t",
                           'QUOTED-PRINTABLE' => '',
                           'X-UUENCODE' => "!\"#$%&'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\]^_\n\r\t",
                           '7BIT' => ''
                          );
        
        // Проверка за BinHex4
        $pos = stripos($text, "BinHex 4");
        if(0 < $pos && $pos  < 40) {
            return array('BINHEX');
        }
                             
        $len = strlen($text);
        for($i=0; $i < $len; $i++) {
            
            $c = $text{$i};
            $cOrd = ord($c);

            foreach($encodings as $name => $allowedChars) {
                if ($name == '7BIT')  {
                    if($cOrd > 127) {

                        return '8BIT';
                    }
                } elseif ($name == 'QUOTED-PRINTABLE') {
                    if( !(($cOrd >= 32 && $cOrd <= 126) || $cOrd == 9 || $cOrd == 10 || $cOrd == 13) ) {

                        unset($encodings[$name]);
                    }

                } elseif(strpos($allowedChars, $c) === FALSE) {
                    unset($encodings[$name]);
                }
            }

            foreach($encodings as $name => $chars) {
                $res[] = $name;
            }

            return $res;
        }
    }

    
    /**
     * Връща рейтингите на различните езици спрямо дадения текст
     */
	function getLgRates($text)
    {
        self::prepareLgAnalyzer();

		// Намираме масива от текаста
		$arr = self::makeLgArray($text, 1000);
 
		foreach(self::$lgAnalyzer as $lg => $dict) {
			foreach($dict as $w => $f) {
				if( $arr[$w] ) {
					$rate[$lg] += $f * $arr[$w]  + 2;
				}
			}
		}

 		return $rate;
	}

    
    /**
     * Подготва масив с 2-3-4 буквени поддуми, които се срещат в текста
     */
	function makeLgArray($text, $maxSubWords = 100)
    {

        $text = str::utf2ascii($text);

		$text = strtolower($text);
		$text = preg_replace('/[^a-z]+/', ' ', "{$text}");
 
		$nText = explode(' ',  $text );
		foreach($nText as $word) {
			if(strlen($word) == 2 || strlen($word) == 3) {
				$count[$word]++;
			} elseif (strlen($word) > 3) {
				$count[substr($word, 0, 3)]++;
				$count[substr($word, -3)]++;
				if(strlen($word) ==  4) {
					$count[$word] += 2;
				}
			}

		}
		
		if(count($count)) {
			arsort(&$count);
			
			$i = 0;
            
            // Отделя само първите 
			foreach($count as $wrd => $freq) {
				$c1[$wrd] = $freq;
				$i++;
				if ($i > $maxSubWords) return $c1;
			}

			return $count;
		}
	}


    /**
     * Опитва се да извлече име на познато кодиране на 
     * двоични данни от зададения стринг
     */
    function canonizeEncoding($encoding)
    {
        $encoding = strtoupper(trim($encoding));

        if(!$encoding) return NULL;
        
        self::prepareEncodingMatchs();
        
        if(self::$encodingsMatchs[$encoding]) {
            $findEncoding = $encoding;
        } else {
            foreach(self::$encodingsMatchs as $key => $name) {
                if(strpos($encoding, (string) $key) !== FALSE) {
                    $findEncoding = $name;
                    break;
                }
            }
        }

        return $findEncoding;
    }


    /**
     * Опитва се да извлече име на позната за iconv() 
     * име на кодировка на символи от зададения стринг
     */
    function canonizeCharset($charset)
    {   
        $charset = strtoupper(trim($charset));
        
        if(!$charset) return NULL;

        // TODO: Да се санитаризира
        
        self::prepareCharsetMatchs();
        
        if(self::$charsetsMatchs[$charset]) {
            $findCharset = $charset;
        } else {
            foreach(self::$charsetsMatchs as $key => $name) {
                if(strpos($charset, (string) $key) !== FALSE) {
                    $findCharset = $name;
                    break;
                }
            }
        }

        if(!$findCharset) {
            $findCharset = substr($charset, 0, 64);
        }

        // Ако функцията iconv разпознава $findCharset като кодова таблица, връщаме $findCharset
        if(iconv($findCharset, 'UTF-8', 'OK') == 'OK') {

            return $findCharset;
        }
        
        return FALSE;
    }


    /**
     * Подготвя анализатора за езици
     */
    private function prepareLgAnalyzer()
    {
        if(!self::$lgAnalyzer) {
            self::$lgAnalyzer = unserialize('a:10:{s:2:"bg";a:101:{s:2:"na";d:24;s:2:"da";d:14;s:4:"shte";d:56;s:3:"ata";d:18;s:4:"tova";d:52;s:2:"za";d:25;s:3:"ite";d:15;s:3:"ane";d:19;s:4:"kato";d:35;s:2:"ot";d:17;s:2:"se";d:5;s:3:"pro";d:3;s:4:"moje";d:14;s:3:"pre";d:6;s:2:"ne";d:26;s:3:"oto";d:12;s:3:"che";d:8;s:3:"eto";d:12;s:2:"po";d:11;s:3:"sht";d:10;s:3:"pri";d:4;s:3:"ova";d:20;s:4:"samo";d:10;s:3:"sia";d:9;s:3:"nya";d:18;s:3:"hte";d:18;s:3:"raz";d:8;s:3:"ato";d:8;s:3:"kat";d:16;s:4:"tozi";d:15;s:3:"ima";d:15;s:3:"tov";d:14;s:3:"ska";d:7;s:3:"nie";d:5;s:2:"si";d:7;s:3:"sta";d:2;s:3:"ybg";d:13;s:3:"tar";d:7;s:3:"ama";d:13;s:3:"sam";d:13;s:3:"ava";d:13;s:4:"edna";d:6;s:3:"izv";d:12;s:3:"pod";d:3;s:3:"nia";d:4;s:3:"kak";d:6;s:3:"van";d:11;s:3:"koy";d:6;s:3:"ost";d:4;s:4:"edin";d:11;s:4:"taka";d:6;s:3:"ako";d:6;s:3:"kom";d:5;s:3:"hto";d:5;s:2:"sa";d:10;s:3:"sas";d:10;s:4:"pari";d:10;s:4:"prez";d:10;s:3:"len";d:5;s:3:"ili";d:10;s:3:"ina";d:2;s:3:"nap";d:5;s:2:"re";d:5;s:3:"rep";d:5;s:4:"sled";d:9;s:3:"kre";d:9;s:3:"mil";d:9;s:3:"hki";d:5;s:4:"data";d:9;s:3:"sii";d:9;s:3:"nes";d:4;s:3:"moj";d:9;s:3:"ski";d:4;s:3:"vla";d:3;s:3:"bez";d:8;s:4:"voip";d:8;s:4:"tazi";d:8;s:4:"bude";d:8;s:3:"nas";d:2;s:3:"par";d:1;s:3:"pra";d:4;s:3:"chi";d:8;s:2:"do";d:4;s:3:"tsi";d:4;s:3:"tse";d:3;s:3:"rab";d:8;s:3:"zap";d:7;s:3:"pub";d:7;s:3:"kam";d:7;s:3:"pos";d:4;s:3:"dat";d:7;s:3:"lno";d:7;s:4:"leva";d:7;s:3:"sto";d:2;s:3:"tak";d:4;s:3:"vam";d:7;s:3:"pol";d:2;s:3:"eni";d:4;s:3:"oje";d:7;s:3:"vsi";d:7;s:2:"bg";d:7;}s:2:"en";a:101:{s:4:"bags";d:61;s:2:"re";d:28;s:3:"the";d:21;s:4:"with";d:30;s:4:"this";d:30;s:3:"com";d:4;s:3:"ing";d:13;s:4:"from";d:25;s:4:"that";d:24;s:4:"line";d:22;s:3:"and";d:11;s:4:"html";d:19;s:4:"dear";d:19;s:4:"mail";d:18;s:4:"sent";d:18;s:4:"your";d:18;s:2:"to";d:17;s:3:"bag";d:17;s:4:"http";d:8;s:2:"in";d:4;s:4:"have";d:16;s:3:"ags";d:16;s:3:"you";d:15;s:4:"card";d:15;s:2:"of";d:13;s:4:"help";d:13;s:3:"con";d:2;s:3:"der";d:6;s:3:"ent";d:4;s:4:"when";d:12;s:3:"pro";d:1;s:4:"roll";d:12;s:2:"is";d:12;s:3:"wor";d:11;s:3:"thi";d:11;s:4:"team";d:11;s:4:"item";d:11;s:3:"sen";d:6;s:4:"best";d:11;s:4:"info";d:10;s:4:"open";d:10;s:3:"loa";d:10;s:3:"ail";d:5;s:4:"show";d:10;s:4:"size";d:9;s:3:"ncy";d:9;s:4:"user";d:9;s:4:"link";d:5;s:3:"eng";d:9;s:3:"lin";d:5;s:2:"we";d:9;s:3:"ish";d:9;s:4:"soma";d:9;s:3:"ion";d:2;s:3:"our";d:4;s:3:"tha";d:8;s:3:"ack";d:8;s:3:"hen";d:4;s:3:"his";d:4;s:3:"for";d:4;s:4:"read";d:8;s:3:"pla";d:4;s:3:"tsm";d:8;s:3:"del";d:3;s:2:"on";d:8;s:3:"ith";d:8;s:3:"wit";d:8;s:3:"rds";d:8;s:4:"most";d:7;s:3:"ess";d:7;s:3:"ext";d:7;s:3:"red";d:7;s:3:"men";d:4;s:4:"also";d:7;s:4:"full";d:7;s:4:"hdpe";d:7;s:4:"lida";d:7;s:3:"rom";d:2;s:3:"ble";d:3;s:3:"sho";d:7;s:3:"hat";d:7;s:3:"ols";d:7;s:3:"age";d:3;s:3:"ste";d:2;s:2:"ep";d:6;s:4:"each";d:6;s:4:"draw";d:6;s:4:"more";d:6;s:4:"list";d:6;s:3:"sgl";d:6;s:3:"rum";d:6;s:3:"new";d:6;s:3:"ine";d:2;s:3:"fro";d:6;s:3:"ers";d:3;s:3:"ear";d:6;s:3:"vac";d:6;s:3:"rol";d:6;s:3:"see";d:6;s:4:"tape";d:6;s:4:"flap";d:6;}s:2:"fr";a:101:{s:2:"de";d:21;s:3:"les";d:16;s:3:"ion";d:9;s:4:"pour";d:42;s:3:"ent";d:12;s:3:"que";d:9;s:2:"la";d:8;s:4:"dans";d:32;s:4:"plus";d:31;s:2:"le";d:15;s:2:"et";d:29;s:4:"sacs";d:29;s:3:"des";d:7;s:4:"vous";d:28;s:2:"en";d:13;s:4:"avec";d:22;s:2:"du";d:21;s:4:"sont";d:20;s:2:"un";d:5;s:3:"pro";d:2;s:4:"mais";d:10;s:3:"par";d:3;s:3:"res";d:6;s:3:"ier";d:19;s:3:"est";d:5;s:3:"ire";d:17;s:3:"sac";d:17;s:3:"art";d:6;s:3:"con";d:3;s:3:"sur";d:16;s:3:"tes";d:5;s:3:"our";d:8;s:3:"ure";d:5;s:3:"ues";d:15;s:3:"eur";d:15;s:3:"com";d:3;s:3:"pou";d:15;s:3:"ous";d:15;s:3:"por";d:4;s:3:"ite";d:5;s:3:"mod";d:5;s:4:"nous";d:14;s:3:"arc";d:14;s:3:"wik";d:3;s:3:"age";d:7;s:3:"ons";d:13;s:3:"une";d:6;s:3:"ant";d:12;s:3:"ail";d:6;s:3:"ine";d:3;s:3:"ere";d:4;s:4:"sous";d:11;s:4:"page";d:11;s:3:"nce";d:11;s:3:"nts";d:11;s:3:"tre";d:5;s:4:"prix";d:10;s:3:"lle";d:5;s:3:"ans";d:10;s:3:"fra";d:10;s:3:"dia";d:3;s:3:"ble";d:5;s:3:"ont";d:10;s:4:"etre";d:10;s:2:"au";d:5;s:3:"ter";d:2;s:3:"urb";d:9;s:3:"gie";d:5;s:3:"son";d:5;s:3:"plu";d:9;s:4:"voir";d:9;s:3:"dan";d:9;s:3:"ais";d:4;s:4:"pont";d:9;s:3:"sou";d:8;s:3:"lus";d:8;s:3:"imp";d:3;s:3:"aut";d:8;s:3:"nde";d:4;s:3:"aux";d:8;s:3:"mon";d:4;s:3:"bio";d:8;s:3:"cat";d:4;s:3:"cle";d:8;s:3:"rie";d:4;s:4:"leur";d:8;s:3:"ces";d:8;s:4:"unis";d:8;s:3:"don";d:8;s:3:"tec";d:8;s:2:"ou";d:4;s:3:"cou";d:8;s:3:"urs";d:7;s:3:"vou";d:7;s:3:"tra";d:2;s:3:"acs";d:7;s:4:"long";d:7;s:3:"sse";d:7;s:3:"rec";d:7;s:3:"pre";d:2;s:3:"ave";d:7;}s:2:"es";a:101:{s:2:"de";d:50;s:2:"la";d:17;s:2:"el";d:50;s:4:"para";d:23;s:3:"con";d:8;s:2:"en";d:22;s:3:"ion";d:9;s:4:"como";d:18;s:3:"los";d:33;s:3:"que";d:8;s:3:"les";d:11;s:3:"nto";d:10;s:3:"com";d:5;s:4:"esta";d:15;s:3:"nes";d:14;s:3:"del";d:9;s:3:"est";d:7;s:3:"ica";d:7;s:3:"nte";d:6;s:3:"des";d:6;s:3:"ria";d:8;s:3:"cue";d:23;s:3:"dos";d:11;s:3:"dad";d:11;s:3:"dia";d:7;s:3:"wik";d:5;s:2:"un";d:5;s:3:"ros";d:10;s:3:"par";d:3;s:2:"es";d:19;s:3:"por";d:5;s:3:"mas";d:10;s:3:"ana";d:10;s:3:"nos";d:9;s:3:"esp";d:18;s:3:"las";d:18;s:3:"hos";d:18;s:3:"tos";d:9;s:3:"art";d:6;s:3:"tel";d:17;s:3:"ado";d:8;s:4:"http";d:8;s:4:"ctra";d:16;s:3:"pro";d:2;s:4:"juan";d:16;s:3:"per";d:4;s:3:"tra";d:3;s:3:"tal";d:8;s:3:"una";d:8;s:3:"ada";d:8;s:3:"ico";d:8;s:3:"his";d:8;s:3:"gen";d:5;s:3:"sta";d:2;s:3:"ina";d:4;s:3:"rio";d:7;s:3:"cia";d:7;s:3:"lla";d:7;s:2:"se";d:3;s:3:"res";d:5;s:3:"ral";d:14;s:3:"ero";d:14;s:3:"ios";d:14;s:3:"ias";d:7;s:3:"ara";d:7;s:3:"gra";d:7;s:3:"ura";d:13;s:3:"san";d:13;s:3:"mar";d:4;s:3:"ras";d:6;s:4:"este";d:6;s:4:"gran";d:11;s:3:"ono";d:6;s:4:"aqui";d:11;s:3:"ter";d:2;s:3:"tes";d:4;s:2:"no";d:5;s:3:"cen";d:11;s:3:"ndo";d:3;s:3:"sal";d:10;s:3:"car";d:3;s:3:"ido";d:10;s:3:"fax";d:10;s:3:"omo";d:5;s:3:"man";d:5;s:3:"rra";d:9;s:3:"tro";d:9;s:3:"tar";d:5;s:3:"bus";d:9;s:3:"rte";d:9;s:3:"ano";d:5;s:3:"bre";d:9;s:3:"num";d:9;s:3:"ind";d:3;s:4:"arte";d:9;s:3:"cas";d:4;s:4:"pero";d:9;s:4:"wiki";d:4;s:4:"solo";d:9;s:4:"cada";d:4;s:3:"gia";d:9;}s:2:"ru";a:101:{s:3:"mol";d:39;s:3:"ogo";d:32;s:3:"kny";d:30;s:3:"aya";d:28;s:3:"pro";d:3;s:3:"nie";d:8;s:4:"godu";d:24;s:4:"flag";d:24;s:3:"kiy";d:24;s:3:"vik";d:12;s:3:"tvo";d:22;s:4:"veka";d:22;s:2:"na";d:5;s:3:"pra";d:10;s:4:"byil";d:19;s:3:"tva";d:17;s:3:"pri";d:3;s:4:"goda";d:16;s:3:"yih";d:15;s:3:"koy";d:8;s:3:"nia";d:5;s:3:"god";d:15;s:3:"byi";d:15;s:3:"koe";d:15;s:3:"sya";d:15;s:3:"bes";d:15;s:3:"pos";d:7;s:3:"ist";d:5;s:3:"vek";d:14;s:3:"tse";d:5;s:4:"chto";d:13;s:3:"ter";d:2;s:3:"yiy";d:13;s:3:"sos";d:12;s:3:"yie";d:12;s:2:"iz";d:12;s:3:"ria";d:4;s:3:"okr";d:12;s:3:"rii";d:6;s:3:"stj";d:11;s:3:"tsa";d:5;s:3:"cha";d:11;s:4:"dlya";d:11;s:3:"per";d:3;s:3:"sta";d:1;s:3:"kot";d:5;s:3:"ali";d:5;s:4:"esht";d:11;s:4:"rusi";d:11;s:3:"tak";d:5;s:3:"gor";d:11;s:3:"isj";d:11;s:3:"pod";d:3;s:3:"zem";d:10;s:3:"imp";d:3;s:3:"vos";d:5;s:3:"pol";d:3;s:3:"oda";d:10;s:3:"nas";d:3;s:2:"po";d:5;s:3:"jyu";d:9;s:3:"ven";d:5;s:3:"kih";d:9;s:3:"iey";d:9;s:3:"iyu";d:9;s:3:"nyi";d:9;s:3:"bii";d:9;s:3:"yim";d:9;s:3:"ami";d:9;s:3:"ntr";d:9;s:3:"kim";d:9;s:3:"val";d:9;s:3:"rod";d:9;s:3:"uyu";d:9;s:3:"sti";d:5;s:3:"itj";d:9;s:3:"ego";d:9;s:3:"ras";d:4;s:4:"yugo";d:8;s:4:"nnoy";d:8;s:3:"voy";d:8;s:3:"sko";d:4;s:3:"kah";d:8;s:2:"ot";d:4;s:4:"xiii";d:8;s:4:"viki";d:8;s:4:"veke";d:8;s:3:"nee";d:8;s:3:"atj";d:8;s:3:"nah";d:7;s:3:"kom";d:4;s:3:"vla";d:3;s:3:"sia";d:4;s:3:"rom";d:3;s:3:"str";d:7;s:3:"noy";d:7;s:3:"nii";d:7;s:3:"vyi";d:7;s:3:"raz";d:4;s:3:"osj";d:7;s:3:"tyi";d:7;}s:2:"mk";a:101:{s:2:"na";d:24;s:3:"ite";d:19;s:3:"ija";d:54;s:3:"ata";d:16;s:2:"vo";d:46;s:4:"bile";d:41;s:4:"kako";d:29;s:4:"shto";d:29;s:2:"od";d:28;s:4:"deka";d:27;s:3:"ina";d:6;s:3:"vos";d:12;s:3:"tsi";d:12;s:2:"za";d:12;s:3:"iot";d:23;s:2:"se";d:4;s:3:"ski";d:10;s:3:"eto";d:10;s:3:"tsa";d:10;s:4:"koja";d:19;s:3:"ska";d:10;s:3:"sko";d:9;s:3:"oto";d:9;s:2:"so";d:18;s:3:"bil";d:18;s:4:"shar";d:17;s:3:"vik";d:8;s:3:"mak";d:16;s:3:"ile";d:8;s:3:"pri";d:3;s:4:"broj";d:15;s:4:"site";d:15;s:3:"pro";d:2;s:3:"sht";d:7;s:3:"eni";d:7;s:3:"den";d:7;s:3:"ovi";d:7;s:3:"ika";d:13;s:3:"edi";d:6;s:2:"da";d:3;s:3:"pre";d:3;s:3:"gra";d:6;s:3:"hka";d:12;s:3:"pol";d:3;s:3:"voj";d:10;s:3:"ure";d:4;s:3:"nie";d:4;s:3:"ana";d:5;s:4:"bila";d:10;s:3:"sha";d:10;s:3:"ani";d:10;s:3:"tse";d:3;s:3:"pla";d:5;s:4:"sela";d:10;s:4:"kade";d:10;s:3:"kot";d:5;s:3:"nas";d:2;s:4:"edna";d:5;s:4:"tsrn";d:10;s:3:"dot";d:10;s:3:"ost";d:3;s:3:"hko";d:9;s:3:"eze";d:9;s:3:"sel";d:9;s:3:"nar";d:9;s:3:"mec";d:9;s:3:"ena";d:8;s:2:"go";d:8;s:3:"nik";d:8;s:3:"dru";d:8;s:3:"ako";d:4;s:3:"sta";d:1;s:3:"sto";d:2;s:3:"vla";d:2;s:3:"naj";d:8;s:3:"gol";d:8;s:3:"tsr";d:8;s:3:"kru";d:8;s:3:"nap";d:4;s:3:"ane";d:4;s:3:"eka";d:7;s:4:"taka";d:4;s:4:"pred";d:7;s:4:"ovoj";d:7;s:4:"bilo";d:7;s:4:"malo";d:7;s:3:"kak";d:4;s:3:"ist";d:2;s:3:"koj";d:7;s:4:"samo";d:4;s:3:"tur";d:4;s:4:"eden";d:7;s:3:"hto";d:4;s:3:"pod";d:2;s:3:"sti";d:4;s:4:"moje";d:4;s:3:"hki";d:4;s:3:"ops";d:7;s:3:"oko";d:7;s:3:"dek";d:7;s:3:"ale";d:2;}s:2:"pt";a:101:{s:2:"de";d:24;s:4:"para";d:33;s:3:"com";d:10;s:3:"cao";d:56;s:3:"con";d:9;s:4:"como";d:24;s:3:"que";d:11;s:4:"pode";d:39;s:3:"nto";d:12;s:3:"nte";d:9;s:3:"uma";d:32;s:4:"mais";d:14;s:2:"um";d:27;s:3:"dos";d:13;s:3:"par";d:4;s:3:"pro";d:3;s:2:"em";d:25;s:3:"por";d:6;s:3:"cia";d:11;s:3:"ado";d:11;s:3:"inf";d:22;s:3:"mos";d:21;s:2:"do";d:11;s:4:"esta";d:10;s:3:"ais";d:10;s:3:"est";d:5;s:2:"se";d:3;s:3:"nao";d:20;s:3:"des";d:5;s:2:"da";d:5;s:3:"ica";d:4;s:3:"pod";d:4;s:3:"ndo";d:6;s:3:"nos";d:9;s:3:"ara";d:9;s:3:"ser";d:17;s:4:"dado";d:17;s:3:"res";d:6;s:3:"ada";d:8;s:3:"tos";d:8;s:2:"ou";d:7;s:3:"mas";d:7;s:3:"ade";d:13;s:3:"oes";d:13;s:4:"cada";d:6;s:4:"isto";d:13;s:3:"sao";d:13;s:3:"dad";d:6;s:3:"das";d:12;s:3:"int";d:3;s:4:"area";d:12;s:3:"alg";d:12;s:3:"omo";d:6;s:2:"no";d:6;s:4:"eles";d:11;s:4:"fato";d:11;s:2:"os";d:11;s:3:"tem";d:11;s:3:"tra";d:2;s:4:"isso";d:10;s:4:"pela";d:10;s:4:"grau";d:10;s:3:"qua";d:5;s:3:"nas";d:3;s:3:"ias";d:5;s:3:"ela";d:10;s:3:"for";d:5;s:3:"ode";d:10;s:3:"ria";d:3;s:4:"suas";d:10;s:3:"ito";d:5;s:3:"sta";d:1;s:3:"car";d:3;s:3:"sso";d:9;s:4:"caso";d:9;s:3:"ass";d:9;s:3:"tes";d:3;s:3:"ele";d:4;s:3:"ent";d:3;s:2:"na";d:2;s:4:"base";d:8;s:4:"seus";d:8;s:3:"cas";d:4;s:4:"algo";d:8;s:3:"mai";d:4;s:3:"ter";d:1;s:2:"as";d:8;s:3:"mat";d:7;s:3:"tal";d:4;s:3:"sim";d:7;s:3:"rep";d:4;s:4:"pois";d:7;s:3:"ros";d:4;s:3:"ico";d:4;s:4:"dois";d:7;s:3:"sua";d:7;s:3:"les";d:2;s:3:"pes";d:7;s:3:"tor";d:7;s:3:"seu";d:7;s:3:"rea";d:3;}s:2:"ro";a:101:{s:2:"de";d:19;s:2:"in";d:14;s:3:"are";d:24;s:3:"rea";d:21;s:3:"lui";d:39;s:4:"care";d:38;s:4:"este";d:16;s:3:"ele";d:16;s:3:"lor";d:30;s:3:"ate";d:14;s:2:"la";d:6;s:3:"con";d:4;s:3:"ale";d:6;s:3:"din";d:22;s:4:"anul";d:21;s:3:"rom";d:7;s:3:"int";d:5;s:3:"ste";d:7;s:3:"tru";d:20;s:3:"mai";d:9;s:4:"cele";d:18;s:3:"ion";d:4;s:3:"pri";d:4;s:4:"fost";d:18;s:3:"par";d:3;s:3:"rii";d:8;s:3:"pro";d:2;s:3:"rie";d:8;s:3:"pen";d:15;s:3:"ile";d:7;s:3:"iei";d:15;s:2:"pe";d:15;s:2:"cu";d:15;s:3:"tul";d:14;s:4:"tiin";d:14;s:2:"ii";d:14;s:4:"prin";d:13;s:2:"al";d:13;s:3:"inc";d:13;s:3:"tre";d:7;s:3:"pol";d:3;s:3:"car";d:4;s:3:"pre";d:3;s:2:"se";d:2;s:4:"unor";d:12;s:3:"uri";d:12;s:3:"com";d:2;s:3:"sta";d:2;s:4:"sunt";d:11;s:3:"mul";d:11;s:3:"lul";d:11;s:3:"uni";d:11;s:3:"est";d:3;s:3:"mod";d:4;s:2:"ti";d:11;s:4:"unei";d:11;s:4:"ceau";d:11;s:4:"acum";d:11;s:4:"mult";d:11;s:4:"alte";d:11;s:3:"imp";d:3;s:3:"buc";d:10;s:3:"cel";d:10;s:3:"man";d:5;s:4:"mare";d:10;s:3:"anu";d:10;s:4:"escu";d:10;s:3:"tic";d:10;s:3:"nia";d:3;s:3:"ice";d:9;s:2:"ie";d:9;s:3:"tra";d:2;s:3:"ure";d:3;s:3:"jur";d:9;s:3:"soc";d:9;s:3:"nul";d:9;s:4:"toat";d:9;s:3:"toa";d:9;s:4:"intr";d:9;s:3:"nea";d:9;s:3:"ial";d:9;s:3:"ind";d:3;s:3:"dez";d:9;s:3:"ace";d:8;s:3:"ast";d:8;s:3:"nte";d:2;s:3:"tea";d:8;s:4:"bune";d:8;s:3:"scu";d:8;s:2:"au";d:4;s:3:"mar";d:3;s:3:"col";d:8;s:3:"ian";d:8;s:3:"une";d:4;s:3:"dec";d:8;s:3:"tat";d:8;s:2:"un";d:2;s:3:"rul";d:8;s:3:"noi";d:7;s:3:"rat";d:7;s:3:"cat";d:4;}s:2:"de";a:101:{s:3:"der";d:27;s:3:"die";d:43;s:3:"und";d:43;s:3:"ten";d:35;s:3:"hen";d:16;s:3:"ein";d:30;s:3:"den";d:14;s:3:"gen";d:9;s:3:"ung";d:25;s:3:"che";d:8;s:3:"sch";d:24;s:2:"in";d:6;s:4:"ammi";d:22;s:3:"ien";d:21;s:4:"eine";d:18;s:3:"all";d:9;s:3:"des";d:4;s:3:"her";d:18;s:3:"lie";d:17;s:3:"and";d:8;s:4:"sind";d:16;s:3:"von";d:16;s:3:"ver";d:16;s:3:"ion";d:3;s:3:"ich";d:14;s:3:"ven";d:7;s:3:"ste";d:4;s:4:"sich";d:13;s:4:"tage";d:13;s:3:"ren";d:13;s:4:"etwa";d:13;s:4:"auch";d:13;s:4:"nach";d:13;s:3:"nen";d:13;s:3:"mon";d:6;s:3:"auf";d:13;s:3:"mit";d:12;s:3:"ber";d:12;s:3:"sen";d:6;s:3:"nus";d:12;s:3:"ges";d:12;s:3:"spo";d:12;s:3:"aft";d:11;s:3:"ter";d:2;s:3:"aus";d:11;s:4:"wird";d:10;s:3:"len";d:5;s:3:"eit";d:10;s:3:"kon";d:10;s:3:"chr";d:10;s:3:"ovi";d:5;s:3:"ers";d:5;s:3:"wei";d:9;s:3:"ern";d:9;s:3:"ine";d:2;s:3:"ort";d:9;s:3:"als";d:9;s:3:"gie";d:4;s:3:"fur";d:9;s:3:"ere";d:3;s:3:"sic";d:9;s:3:"eln";d:9;s:3:"men";d:4;s:3:"ost";d:3;s:3:"art";d:3;s:3:"nde";d:4;s:2:"im";d:8;s:3:"fus";d:8;s:3:"mus";d:8;s:3:"tur";d:4;s:3:"alt";d:4;s:3:"sta";d:1;s:4:"noch";d:7;s:3:"mar";d:3;s:4:"bern";d:7;s:3:"nac";d:7;s:4:"kann";d:7;s:3:"reg";d:7;s:3:"per";d:2;s:3:"cht";d:7;s:3:"amm";d:7;s:3:"ang";d:7;s:3:"int";d:1;s:3:"ica";d:1;s:3:"tik";d:6;s:3:"the";d:3;s:3:"bei";d:6;s:3:"lan";d:6;s:3:"bis";d:6;s:3:"ale";d:1;s:3:"tag";d:6;s:3:"por";d:1;s:3:"ist";d:2;s:4:"nost";d:6;s:4:"budu";d:6;s:3:"kel";d:6;s:3:"zei";d:6;s:3:"vor";d:6;s:3:"wir";d:6;s:3:"ben";d:6;s:3:"wik";d:1;}s:2:"it";a:101:{s:4:"vedi";d:92;s:2:"di";d:63;s:3:"ica";d:12;s:3:"wik";d:11;s:3:"mod";d:14;s:3:"che";d:12;s:3:"dia";d:12;s:3:"del";d:11;s:3:"one";d:31;s:4:"voce";d:30;s:3:"edi";d:15;s:3:"are";d:15;s:4:"sono";d:29;s:3:"nte";d:7;s:3:"pag";d:26;s:3:"per";d:6;s:2:"in";d:6;s:3:"con";d:4;s:4:"voci";d:24;s:3:"ved";d:24;s:2:"un";d:6;s:4:"come";d:22;s:2:"la";d:5;s:2:"il";d:21;s:3:"lla";d:10;s:3:"pro";d:2;s:3:"una";d:9;s:3:"ina";d:5;s:3:"ine";d:4;s:3:"ale";d:4;s:3:"ato";d:8;s:3:"com";d:3;s:3:"uto";d:15;s:3:"tto";d:15;s:2:"le";d:7;s:3:"ita";d:14;s:4:"alla";d:14;s:3:"voc";d:14;s:3:"non";d:13;s:4:"wiki";d:6;s:3:"oni";d:12;s:3:"ere";d:4;s:3:"ono";d:6;s:3:"lle";d:6;s:3:"nti";d:12;s:3:"ese";d:11;s:3:"all";d:6;s:3:"sta";d:1;s:3:"ndo";d:4;s:3:"aiu";d:11;s:4:"link";d:5;s:3:"ute";d:11;s:2:"da";d:3;s:3:"nto";d:4;s:3:"ano";d:5;s:3:"qua";d:5;s:2:"si";d:5;s:4:"edit";d:10;s:3:"ing";d:5;s:3:"int";d:3;s:3:"tra";d:2;s:4:"dell";d:9;s:4:"nell";d:9;s:4:"dall";d:9;s:3:"ate";d:5;s:3:"par";d:2;s:3:"nel";d:9;s:3:"ata";d:3;s:3:"alt";d:5;s:4:"logo";d:9;s:3:"anc";d:9;s:3:"sto";d:3;s:3:"ati";d:8;s:3:"oce";d:8;s:3:"dis";d:8;s:3:"dal";d:8;s:4:"cosa";d:8;s:3:"gio";d:8;s:3:"ore";d:8;s:3:"son";d:4;s:3:"ome";d:8;s:3:"que";d:2;s:2:"su";d:7;s:4:"alle";d:7;s:3:"pri";d:1;s:4:"vuoi";d:7;s:4:"ogni";d:7;s:3:"lin";d:4;s:4:"nome";d:7;s:3:"ori";d:7;s:3:"rio";d:3;s:3:"gli";d:7;s:3:"gen";d:2;s:3:"ind";d:2;s:2:"se";d:1;s:3:"ito";d:3;s:3:"oci";d:6;s:3:"ter";d:1;s:2:"ad";d:6;s:3:"era";d:6;s:3:"ali";d:3;}}');
		

         /*   foreach(self::$lgAnalyzer as $lg => $words) {
                foreach($words as $w => $f) {
                    $a[$lg] += $f;
                }
            }

            bp($a);
            */
        }
    }


    /**
     * Помощна функция за сортиране според дължината на ключа
     */
    private function sort($a, $b)
    {
        return strlen($b) - strlen($a);
    }


    /**
     * Подготвя масив с ключове - алиаси на чарсетове и стойности - официални имена на чарсетове
     * Масивът е подреден от по-дългите ключове към по-късите
     */
    private static function prepareCharsetMatchs()
    {
        if(count(self::$charsetsMatchs)) {
            return;
        }
        
        /**
         * Сурова информация за символните кодировки
         * Източник: http://asis.epfl.ch/GNU.MISC/recode-3.6/recode_6.html
         */
        $charsets = array(
            
            // General character sets
            'US-ASCII' => 'ASCII, ISO646-US, ISO_646.IRV:1991, ISO-IR-6, ANSI_X3.4-1968, CP367, IBM367, US, csASCII, ISO646.1991-IRV, ASCI', 

            // General multi-byte encodings
            'UTF-8' => 'UTF8,UTF',
            'UCS-2' => 'ISO-10646-UCS-2',
            'UCS-2BE' => 'UNICODEBIG, UNICODE-1-1, csUnicode11',
            'UCS-2LE' => 'UNICODELITTLE',
            'UCS-4' => 'ISO-10646-UCS-4, csUCS4',
            'UCS-4BE',
            'UCS-4LE',
            'UTF-16',
            'UTF-16BE',
            'UTF-16LE',
            'UTF-7' => 'UNICODE-1-1-UTF-7, csUnicode11UTF7',
            'UCS-2-INTERNAL',
            'UCS-2-SWAPPED',
            'UCS-4-INTERNAL',
            'UCS-4-SWAPPED',
            'JAVA',

            // Standard 8-bit encodings
            'ISO-8859-10' => '8859-10, ISO_8859-10:1992, ISO-IR-157, LATIN6, L6, csISOLatin6, ISO8859-10', 
            'ISO-8859-13' => '8859-13, ISO-IR-179, LATIN7, L7', 
            'ISO-8859-14' => '8859-14, ISO_8859-14:1998, ISO-IR-199, LATIN8, L8', 
            'ISO-8859-15' => '8859-15, ISO_8859-15:1998, ISO-IR-203', 
            'ISO-8859-16' => '8859-16, ISO_8859-16:2000, ISO-IR-226', 
            'ISO-8859-5' => 'ISO_8859-5, ISO_8859-5:1988, ISO-IR-144, CYRILLIC, csISOLatinCyrillic, 8859-5, 8859_5', 
            'ISO-8859-1' => 'ISO_8859-1, ISO_8859-1:1987, ISO-IR-100, CP819, IBM819, LATIN1, L1, csISOLatin1, ISO8859-1, 8859_1,8859-1,8859', 
            'ISO-8859-2' => 'ISO_8859-2, ISO_8859-2:1987, ISO-IR-101, LATIN2, L2, csISOLatin2, 8859-2, 8859_2', 
            'ISO-8859-3' => 'ISO_8859-3, ISO_8859-3:1988, ISO-IR-109, LATIN3, L3, csISOLatin3, 8859-3, 8859_3', 
            'ISO-8859-4' => 'ISO_8859-4, ISO_8859-4:1988, ISO-IR-110, LATIN4, L4, csISOLatin4, 8859-4, 8859_4', 
            'ISO-8859-6' => 'ISO_8859-6, ISO_8859-6:1987, ISO-IR-127, ECMA-114, ASMO-708, ARABIC, csISOLatinArabic, 8859-6, 8859_6', 
            'ISO-8859-7' => 'ISO_8859-7, ISO_8859-7:1987, ISO-IR-126, ECMA-118, ELOT_928, GREEK8, GREEK, csISOLatinGreek, 8859-7, 8859_7', 
            'ISO-8859-8' => 'ISO_8859-8, ISO_8859-8:1988, ISO-IR-138, HEBREW, csISOLatinHebrew, ISO8859-8, 8859_8', 
            'ISO-8859-9' => 'ISO_8859-9, ISO_8859-9:1989, ISO-IR-148, LATIN5, L5, csISOLatin5, ISO8859-9, 8859_9', 
            'KOI8-R' => 'csKOI8R,KOI8R', 
            'KOI8-U',
            'KOI8-RU',

            // Windows 8-bit encodings
            'CP1250' => '1250, MS-EE', 
            'CP1251' => '1251, MS-CYRL, WINDOWS-BG, WIN-BG', 
            'CP1252' => '1252, MS-ANSI', 
            'CP1253' => '1253, MS-GREEK', 
            'CP1254' => '1254, MS-TURK', 
            'CP1255' => '1255, MS-HEBR', 
            'CP1256' => '1256, MS-ARAB', 
            'CP1257' => '1257, WINBALTRIM', 
            'CP1258' => '1258', 

            // DOS 8-bit encodings
            'CP850' => 'IBM850, 850, csPC850Multilingual', 
            'CP866' => 'IBM866, 866, csIBM866', 

            // Macintosh 8-bit encodings
            'MacRoman' => 'Macintosh, MAC, csMacintosh', 
            'MacCentralEurope',
            'MacIceland',
            'MacCroatian',
            'MacRomania',
            'MacCyrillic',
            'MacUkraine',
            'MacGreek',
            'MacTurkish',
            'MacHebrew',
            'MacArabic',
            'MacThai',

            // Other platform specific 8-bit encodings
            'HP-ROMAN8' => 'ROMAN8, R8, csHPRoman8', 
            'NEXTSTEP',

            // Regional 8-bit encodings used for a single language
            'ARMSCII-8',
            'Georgian-Academy',
            'Georgian-PS',
            'MuleLao-1',
            'CP1133' => 'IBM-CP1133',
            'TIS-620' => 'TIS620, TIS620-0, TIS620.2529-1, TIS620.2533-0, TIS620.2533-1, ISO-IR-166', 
            'CP874' => 'WINDOWS-874',
            'VISCII' => 'VISCII1.1-1, csVISCII', 
            'TCVN' =>  'TCVN-5712, TCVN5712-1, TCVN5712-1:1993', 

            // CJK character sets (not documented)
            'JIS_C6220-1969-RO' => 'ISO646-JP, ISO-IR-14, JP, csISO14JISC6220ro', 
            'JIS_X0201' => 'JISX0201-1976, X0201, csHalfWidthKatakana, JISX0201.1976-0, JIS0201', 
            'JIS_X0208' => 'JIS_X0208-1983, JIS_X0208-1990, JIS0208, X0208, ISO-IR-87, csISO87JISX0208, JISX0208.1983-0, JISX0208.1990-0, JIS0208', 
            'JIS_X0212' => 'JIS_X0212.1990-0, JIS_X0212-1990, X0212, ISO-IR-159, csISO159JISX02121990, JISX0212.1990-0, JIS0212', 
            'GB_1988-80' => 'ISO646-CN, ISO-IR-57, CN, csISO57GB1988', 
            'GB_2312-80' => 'ISO-IR-58, csISO58GB231280, CHINESE, GB2312.1980-0', 
            'ISO-IR-165' => 'CN-GB-ISOIR165',
            'KSC_5601' => 'KS_C_5601-1987, KS_C_5601-1989, ISO-IR-149, csKSC56011987, KOREAN, KSC5601.1987-0, KSX1001:1992, 5601', 

            // CJK encodings
            'EUC-JP' => 'EUCJP, Extended_UNIX_Code_Packed_Format_for_Japanese, csEUCPkdFmtJapanese, EUC_JP', 
            'SJIS' => 'SHIFT_JIS, SHIFT-JIS, MS_KANJI, csShiftJIS', 
            'CP932',
            'ISO-2022-JP' => '2022JP, ISO2022JP', 
            'ISO-2022-JP-1' => '2022JP1',
            'ISO-2022-JP-2' => '2022JP2',
            'EUC-CN' => 'EUCCN, GB2312, CN-GB, csGB2312, EUC_CN', 
            'GBK' => 'CP936', 
            'GB18030',
            'ISO-2022-CN' => 'csISO2022CN, ISO2022CN', 
            'ISO-2022-CN-EXT',
            'HZ' => 'HZ-GB-2312', 
            'EUC-TW' => 'EUCTW, csEUCTW, EUC_TW', 
            'BIG5' => 'BIG-5, BIG-FIVE, BIGFIVE, CN-BIG5, csBig5', 
            'CP950',
            'BIG5HKSCS',
            'EUC-KR' => 'EUCKR, csEUCKR, EUC_KR', 
            'CP949' => 'UHC',
            'JOHAB' => 'CP1361', 
            'ISO-2022-KR' => 'csISO2022KR, ISO2022KR', 
            'WCHAR_T',
        );

        foreach($charsets as $name => $al) {

            if( is_int($name) ) $name = $al;
            
            $name = strtoupper(trim($name));
            expect(!self::$charsetsMatchs[$name]);
            self::$charsetsMatchs[$name] = $name;

            foreach(explode(",", $al) as $a) {
                $a = strtoupper(trim($a));
                expect(!self::$charsetsMatchs[$а]);
                self::$charsetsMatchs[$a] = $name;
            }
        }

        uksort(self::$charsetsMatchs, 'lang_Encoding::sort');
    }
    
    
    /**
     * Подготвя масив с ключове - алиаси на кодиране на бинарни данни
     * Масивът е подреден от по-дългите ключове към по-късите
     */
    private static function prepareEncodingMatchs()
    {
        if(count(self::$encodingsMatchs)) {
            return;
        }

        // Масив с най-често срещаните encoding-s
        $encodings = array(
            'QUOTED-PRINTABLE' => 'quoted-print,quoted,q',
            'BASE64' => 'base,64',
            'X-UUENCODE' => 'uu',
            '7BIT' => '7',
            '8BIT' => '8',
            'BINHEX'
        );

        foreach($encodings as $name => $al) {

            if( is_int($name) ) $name = $al;
            
            $name = strtoupper(trim($name));
            expect(!self::$encodingsMatchs[$name]);
            self::$encodingsMatchs[$name] = $name;

            foreach(explode(",", $al) as $a) {
                $a = strtoupper(trim($a));
                expect(!self::$encodingsMatchs[$а]);
                self::$encodingsMatchs[$a] = $name;
            }
        }

        uksort(self::$encodingsMatchs, 'lang_Encoding::sort');
    }

}