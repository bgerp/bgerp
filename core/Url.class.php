<?php



/**
 * Клас 'core_Url' ['url'] - Функции за за работа със URL
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Url
{
    
	 // От http://data.iana.org/TLD/tlds-alpha-by-domain.txt
     static $valideTld = array( 'abogado',  'ac',  'academy',  'accountants',  'active',  'actor',  'ad',  'adult',  
     							'ae',  'aero',  'af',  'ag',  'agency',  'ai',  'airforce',  'al',  'allfinanz',  
     							'alsace',  'am',  'amsterdam',  'an',  'android',  'ao',  'apartments',  'aq',  'aquarelle', 
      							'ar',  'archi',  'army',  'arpa',  'as',  'asia',  'associates',  'at',  'attorney',  'au',  
     							'auction',  'audio',  'autos',  'aw',  'ax',  'axa',  'az',  'ba',  'band',  'bank',  'bar',  
     							'barclaycard',  'barclays',  'bargains',  'bayern',  'bb',  'bd',  'be',  'beer',  'berlin',  
     							'best',  'bf',  'bg',  'bh',  'bi',  'bid',  'bike',  'bingo',  'bio',  'biz',  'bj',  'black',  
     							'blackfriday',  'bloomberg',  'blue',  'bm',  'bmw',  'bn',  'bnpparibas',  'bo',  'boats',  'boo',  
     							'boutique',  'br',  'brussels',  'bs',  'bt',  'budapest',  'build',  'builders',  'business',  'buzz',  
     							'bv',  'bw',  'by',  'bz',  'bzh',  'ca',  'cab',  'cal',  'camera',  'camp',  'cancerresearch',  'canon',  
     							'capetown',  'capital',  'caravan',  'cards',  'care',  'career',  'careers',  'cartier',  'casa',  
     							'cash',  'casino',  'cat',  'catering',  'cbn',  'cc',  'cd',  'center',  'ceo',  'cern',  'cf',  'cg',  
     							'ch',  'channel',  'chat',  'cheap',  'christmas',  'chrome',  'church',  'ci',  'citic',  'city',  'ck', 
      							'cl',  'claims',  'cleaning',  'click',  'clinic',  'clothing',  'club',  'cm',  'cn',  'co',  'coach',  
     							'codes',  'coffee',  'college',  'cologne',  'com',  'community',  'company',  'computer',  'condos',  
     							'construction',  'consulting',  'contractors',  'cooking',  'cool',  'coop',  'country',  'courses',  'cr',  
     							'credit',  'creditcard',  'cricket',  'crs',  'cruises',  'cu',  'cuisinella',  'cv',  'cw',  'cx',  'cy',  
     							'cymru',  'cz',  'dabur',  'dad',  'dance',  'dating',  'day',  'dclk',  'de',  'deals',  'degree',  'delivery',  
     							'democrat',  'dental',  'dentist',  'desi',  'design',  'dev',  'diamonds',  'diet',  'digital',  'direct',  
     							'directory',  'discount',  'dj',  'dk',  'dm',  'dnp',  'do',  'docs',  'domains',  'doosan',  'durban',  
     							'dvag',  'dz',  'eat',  'ec',  'edu',  'education',  'ee',  'eg',  'email',  'emerck',  'energy',  'engineer',  
     							'engineering',  'enterprises',  'equipment',  'er',  'es',  'esq',  'estate',  'et',  'eu',  'eurovision',  
     							'eus',  'events',  'everbank',  'exchange',  'expert',  'exposed',  'fail',  'fans',  'farm',  'fashion',  
     							'feedback',  'fi',  'finance',  'financial',  'firmdale',  'fish',  'fishing',  'fit',  'fitness',  'fj',  
     							'fk',  'flights',  'florist',  'flowers',  'flsmidth',  'fly',  'fm',  'fo',  'foo',  'football',  'forsale',  
     							'foundation',  'fr',  'frl',  'frogans',  'fund',  'furniture',  'futbol',  'ga',  'gal',  'gallery',  'garden',  
     							'gb',  'gbiz',  'gd',  'gdn',  'ge',  'gent',  'gf',  'gg',  'ggee',  'gh',  'gi',  'gift',  'gifts',  'gives',  
     							'gl',  'glass',  'gle',  'global',  'globo',  'gm',  'gmail',  'gmo',  'gmx',  'gn',  'goldpoint',  'goog',  
     							'google',  'gop',  'gov',  'gp',  'gq',  'gr',  'graphics',  'gratis',  'green',  'gripe',  'gs',  'gt',  
     							'gu',  'guide',  'guitars',  'guru',  'gw',  'gy',  'hamburg',  'hangout',  'haus',  'healthcare',  'help',  
     							'here',  'hermes',  'hiphop',  'hiv',  'hk',  'hm',  'hn',  'holdings',  'holiday',  'homes',  'horse',  
     							'host',  'hosting',  'house',  'how',  'hr',  'ht',  'hu',  'ibm',  'id',  'ie',  'ifm',  'il',  'im',  'immo',  
     							'immobilien',  'in',  'industries',  'info',  'ing',  'ink',  'institute',  'insure',  'int',  'international',  
     							'investments',  'io',  'iq',  'ir',  'irish',  'is',  'it',  'iwc',  'jcb',  'je',  'jetzt',  'jm',  'jo',  'jobs',  
     							'joburg',  'jp',  'juegos',  'kaufen',  'kddi',  'ke',  'kg',  'kh',  'ki',  'kim',  'kitchen',  'kiwi',  'km',  'kn',  
     							'koeln',  'kp',  'kr',  'krd',  'kred',  'kw',  'ky',  'kyoto',  'kz',  'la',  'lacaixa',  'land',  'lat',  'latrobe',  
     							'lawyer',  'lb',  'lc',  'lds',  'lease',  'legal',  'lgbt',  'li',  'lidl',  'life',  'lighting',  'limited',  'limo',  
     							'link',  'lk',  'loans',  'london',  'lotte',  'lotto',  'lr',  'ls',  'lt',  'ltda',  'lu',  'luxe',  'luxury',  
     							'lv',  'ly',  'ma',  'madrid',  'maison',  'management',  'mango',  'market',  'marketing',  'marriott',  'mc',  
     							'md',  'me',  'media',  'meet',  'melbourne',  'meme',  'memorial',  'menu',  'mg',  'mh',  'miami',  'mil',  'mini',  
     							'mk',  'ml',  'mm',  'mn',  'mo',  'mobi',  'moda',  'moe',  'monash',  'money',  'mormon',  'mortgage',  'moscow',  
     							'motorcycles',  'mov',  'mp',  'mq',  'mr',  'ms',  'mt',  'mu',  'museum',  'mv',  'mw',  'mx',  'my',  'mz',  
     							'na',  'nagoya',  'name',  'navy',  'nc',  'ne',  'net',  'network',  'neustar',  'new',  'nexus',  'nf',  'ng',  
     							'ngo',  'nhk',  'ni',  'nico',  'ninja',  'nl',  'no',  'np',  'nr',  'nra',  'nrw',  'ntt',  'nu',  'nyc',  'nz',  
     							'okinawa',  'om',  'one',  'ong',  'onl',  'ooo',  'org',  'organic',  'osaka',  'otsuka',  'ovh',  'pa',  'paris',  
     							'partners',  'parts',  'party',  'pe',  'pf',  'pg',  'ph',  'pharmacy',  'photo',  'photography',  'photos',  
     							'physio',  'pics',  'pictures',  'pink',  'pizza',  'pk',  'pl',  'place',  'plumbing',  'pm',  'pn',  'pohl',  
     							'poker',  'porn',  'post',  'pr',  'praxi',  'press',  'pro',  'prod',  'productions',  'prof',  'properties',  
     							'property',  'ps',  'pt',  'pub',  'pw',  'py',  'qa',  'qpon',  'quebec',  're',  'realtor',  'recipes',  'red',  
     							'rehab',  'reise',  'reisen',  'reit',  'ren',  'rentals',  'repair',  'report',  'republican',  'rest',  
     							'restaurant',  'reviews',  'rich',  'rio',  'rip',  'ro',  'rocks',  'rodeo',  'rs',  'rsvp',  'ru',  'ruhr',  
     							'rw',  'ryukyu',  'sa',  'saarland',  'sale',  'samsung',  'sarl',  'saxo',  'sb',  'sc',  'sca',  'scb',  
     							'schmidt',  'school',  'schule',  'schwarz',  'science',  'scot',  'sd',  'se',  'services',  'sew',  'sexy',  
     							'sg',  'sh',  'shiksha',  'shoes',  'shriram',  'si',  'singles',  'sj',  'sk',  'sky',  'sl',  'sm',  'sn',  
     							'so',  'social',  'software',  'sohu',  'solar',  'solutions',  'soy',  'space',  'spiegel',  'sr',  'st',  
     							'study',  'style',  'su',  'sucks',  'supplies',  'supply',  'support',  'surf',  'surgery',  'suzuki',  
     							'sv',  'sx',  'sy',  'sydney',  'systems',  'sz',  'taipei',  'tatar',  'tattoo',  'tax',  'tc',  'td',  
     							'technology',  'tel',  'temasek',  'tennis',  'tf',  'tg',  'th',  'tienda',  'tips',  'tires',  'tirol',  
     							'tj',  'tk',  'tl',  'tm',  'tn',  'to',  'today',  'tokyo',  'tools',  'top',  'toshiba',  'town',  'toys',  
     							'tp',  'tr',  'trade',  'training',  'travel',  'trust',  'tt',  'tui',  'tv',  'tw',  'tz',  'ua',  'ug',  
     							'uk',  'university',  'uno',  'uol',  'us',  'uy',  'uz',  'va',  'vacations',  'vc',  've',  'vegas',  
     							'ventures',  'versicherung',  'vet',  'vg',  'vi',  'viajes',  'video',  'villas',  'vision',  'vlaanderen',  
     							'vn',  'vodka',  'vote',  'voting',  'voto',  'voyage',  'vu',  'wales',  'wang',  'watch',  'webcam',  'website',  
     							'wed',  'wedding',  'wf',  'whoswho',  'wien',  'wiki',  'williamhill',  'wme',  'work',  'works',  'world',  
     							'ws',  'wtc',  'wtf',  'xn--1qqw23a',  'xn--3bst00m',  'xn--3ds443g',  'xn--3e0b707e',  'xn--45brj9c',  'xn--45q11c',  
     							'xn--4gbrim',  'xn--55qw42g',  'xn--55qx5d',  'xn--6frz82g',  'xn--6qq986b3xl',  'xn--80adxhks',  'xn--80ao21a',  
     							'xn--80asehdb',  'xn--80aswg',  'xn--90a3ac',  'xn--90ais',  'xn--b4w605ferd',  'xn--c1avg',  'xn--cg4bki',  
     							'xn--clchc0ea0b2g2a9gcd',  'xn--czr694b',  'xn--czrs0t',  'xn--czru2d',  'xn--d1acj3b',  'xn--d1alf',  'xn--fiq228c5hs',  
     							'xn--fiq64b',  'xn--fiqs8s',  'xn--fiqz9s',  'xn--flw351e',  'xn--fpcrj9c3d',  'xn--fzc2c9e2c',  'xn--gecrj9c',  
     							'xn--h2brj9c',  'xn--hxt814e',  'xn--i1b6b1a6a2e',  'xn--io0a7i',  'xn--j1amh',  'xn--j6w193g',  'xn--kprw13d',  
     							'xn--kpry57d',  'xn--kput3i',  'xn--l1acc',  'xn--lgbbat1ad8j',  'xn--mgb9awbf',  'xn--mgba3a4f16a',  'xn--mgbaam7a8h',  
     							'xn--mgbab2bd',  'xn--mgbayh7gpa',  'xn--mgbbh1a71e',  'xn--mgbc0a9azcg',  'xn--mgberp4a5d4ar',  'xn--mgbx4cd0ab',  
     							'xn--ngbc5azd',  'xn--node',  'xn--nqv7f',  'xn--nqv7fs00ema',  'xn--o3cw4h',  'xn--ogbpf8fl',  'xn--p1acf',  'xn--p1ai',  
     							'xn--pgbs0dh',  'xn--q9jyb4c',  'xn--qcka1pmc',  'xn--rhqv96g',  'xn--s9brj9c',  'xn--ses554g',  'xn--unup4y',  
     							'xn--vermgensberater-ctb',  'xn--vermgensberatung-pwb',  'xn--vhquv',  'xn--wgbh1c',  'xn--wgbl6a',  'xn--xhq521b',  
     							'xn--xkc2al3hye2a',  'xn--xkc2dl3a5ee0h',  'xn--yfro4i67o',  'xn--ygbi2ammx',  'xn--zfr164b',  'xxx',  'xyz',  
     							'yachts',  'yandex',  'ye',  'yodobashi',  'yoga',  'yokohama',  'youtube',  'yt',  'za',  'zip',  'zm',  'zone',  
     							'zuerich',  'zw');
    
    /**
     * @todo Чака за документация...
     */
    static function parseUrl(&$url)
    {
        if (strlen($url) <= 300) {
            $r = "(?:([a-z0-9+-._]+)://)?";
            $r .= "(?:";
            $r .= "(?:((?:[a-z0-9-._~!$&'()*+,;=:]|%[0-9a-f]{2})*)@)?";
            $r .= "(?:\[((?:[a-z0-9:])*)\])?";
            $r .= "((?:[a-z0-9-._~!$&'()*+,;=]|%[0-9a-f]{2})*)";
            $r .= "(?::(\d*))?";
            $r .= "(/(?:[a-z0-9-._~!$&'()*+,;=:@/]|%[0-9a-f]{2})*)?";
            $r .= "|";
            $r .= "(/?";
            $r .= "(?:[a-z0-9-._~!$&'()*+,;=:@]|%[0-9a-f]{2})+";
            $r .= "(?:[a-z0-9-._~!$&'()*+,;=:@\/]|%[0-9a-f]{2})*";
            $r .= ")?";
            $r .= ")";
            $r .= "(?:\?((?:[a-z0-9-._~!$&'()*+,;=:\/?@]|%[0-9a-f]{2})*))?";
            $r .= "(?:#((?:[a-z0-9-._~!$&'()*+,;=:\/?@]|%[0-9a-f]{2})*))?";
            
            preg_match("`$r`i", $url, $match);
            
            $parts = array(
                "scheme" => '',
                "userinfo" => '',
                "authority" => '',
                "host" => '',
                "port" => '',
                "path" => '',
                "query" => '',
                "fragment" => ''
            );
            
            switch (count($match)) {
                case 10 :
                    $parts['fragment'] = $match[9];
                case 9 :
                    $parts['query'] = $match[8];
                case 8 :
                    $parts['path'] = $match[7];
                case 7 :
                    $parts['path'] = $match[6] . $parts['path'];
                case 6 :
                    $parts['port'] = $match[5];
                case 5 :
                    $parts['host'] = $match[3] ? "[" . $match[3] . "]" : $match[4];
                case 4 :
                    $parts['userinfo'] = $match[2];
                case 3 :
                    $parts['scheme'] = $match[1];
            }
            $parts['authority'] = ($parts['userinfo'] ? $parts['userinfo'] . "@" : "") . $parts['host'] . ($parts['port'] ? ":" . $parts['port'] : "");
        } else {
            $parts = parse_url($url);
        }
        
        if ($parts['query']) {
            $parts['query_params'] = array();
            $aPairs = explode('&', $parts['query']);
            
            foreach ($aPairs as $sPair) {
                if (trim($sPair) == '') {
                    continue;
                }
                list($sKey, $sValue) = explode('=', $sPair);
                $parts['query_params'][$sKey] = decodeUrl($sValue);
            }
        }
        
        if (empty($parts['scheme'])) {
            if (strpos($parts['host'], 'ftp') === 0) {
                $parts['scheme'] = 'ftp';
            } else {
                $parts['scheme'] = 'http';
            }
            
            $url = $parts['scheme'] . '://' . $url;
        }
        
        $parts['scheme'] = strtolower($parts['scheme']);
        
        if ($parts['host']) {
            $parts['host'] = strtolower($parts['host']);
            $domainPttr = "/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.(?P<tld>[a-z\.]{2,6}))$/i";
            
            if (preg_match($domainPttr, $parts['host'], $match)) {
                $parts['domain'] = $match['domain'];
                $parts['tld'] = strtolower($match['tld']);
            }
        }
        
        if ($parts['path']) {
            setIfNot($parts, pathInfo(decodeUrl($parts['path'])));
        }
        
        if (!core_URL::isValidUrl($url)) {
            $parts['error'] = "Невалидно URL";
        } elseif ($parts['tld'] && !in_array($parts['tld'], self::$valideTld)) {
            $parts['error'] = "Невалидно разширение на домейн|*: <b>" . $parts['tld'] . "</b>";
        }
        
        return $parts;
    }
    

    /**
     * Проверява дали е валиден даден топ-левъл домейн
     * Ако в домейна има точка, се взема последното след точката
     */
    static function isValidTld($tld)
    {
        if(FALSE !== ($dotPos = strrpos($tld, '.'))) {
            $tld = substr($tld, $dotPos + 1);
        }

        $tld = strtolower($tld);

        if (in_array($tld, self::$valideTld)) {
        	   
        	return TRUE;
        }
       
        return FALSE;
    }
    
    /**
     * Проверява дали дадено URL е валидно
     */
    static function isValidUrl2($url)
    {
        // схема 
        $urlregex = "^([a-z0-9+-._]+)\:\/\/";
        
        // USER и PASS (опционално)
        $urlregex .= "([a-z0-9+!*(),;?&=\$_.-]+(\:[a-z0-9+!*(),;?&=\$_.-]+)?@)?";
        
        // HOSTNAME или IP
        $urlregex .= "[a-z0-9+\$_-]+(\.[a-z0-9+\$_-]+)*";     // http://x = allowed (ex. http://localhost, http://routerlogin)
        //$urlregex .= "[a-z0-9+\$_-]+(\.[a-z0-9+\$_-]+)+";  // http://x.x = minimum
        //$urlregex .= "([a-z0-9+\$_-]+\.)*[a-z0-9+\$_-]{2,3}";  // http://x.xx(x) = minimum
        //use only one of the above
        
        // PORT (опционално)
        $urlregex .= "(\:[0-9]{2,5})?";
        
        // PATH  (optional)
        $urlregex .= "(\/([a-z0-9+\%\$_-]\.?)+)*\/?";
        
        // GET Query (optional)
        $urlregex .= "(\?[a-z+&\$_.-][a-z0-9;:@/&%=+\$_.-]*)?";
        
        // ANCHOR (optional)
        $urlregex .= "(#[a-z_.-][a-z0-9+\$_.-]*)?\$";
        
        // check
        $res = eregi($urlregex, $url) ? TRUE : FALSE;
        
        return $res;
    }
    
    
    /**
     * This function should only be used on actual URLs. It should not be used for
     * Drupal menu paths, which can contain arbitrary characters.
     * Valid values per RFC 3986.
     *
     * @param $url
     * The URL to verify.
     * TRUE if the URL is in a valid format.
     */
    static function isValidUrl($url, $absolute = TRUE)
    {
        if ($absolute) {
            $res = (bool) preg_match("/^" . # Start at the beginning of the text
                "(?:[a-z0-9+-._]+?):\/\/" . # Look for ftp, http, or https schemes
                "(?:" . # Userinfo (optional) which is typically
                "(?:(?:[\w\.\-\+!$&'\(\)*\+,;=]|%[0-9a-f]{2})+:)*" . # a username or a username and password
                "(?:[\w\.\-\+%!$&'\(\)*\+,;=]|%[0-9a-f]{2})+@" . # combination
                ")?" . "(?:" . "(?:[a-z0-9\-\.]|%[0-9a-f]{2})+" . # A domain name or a IPv4 address
                "|(?:\[(?:[0-9a-f]{0,4}:)*(?:[0-9a-f]{0,4})\])" . # or a well formed IPv6 address
                ")" . "(?::[0-9]+)?" . # Server port number (optional)
                "(?:[\/|\?]" . "(?:[\w#!:\.\?\+=&@$'~*,;\/\(\)\[\]\-]|%[0-9a-f]{2})" . # The path and query (optional)
                "*)?" . "$/xi", $url, $m);
        } else {
            $res = preg_match("/^(?:[\w#!:\.\?\+=&@$'~*,;\/\(\)\[\]\-]|%[0-9a-f]{2})+$/i", $url);
        }
        
        return $res;
    }
    
    
    /**
     * Link: http://www.bin-co.com/php/scripts/load/
     * Version : 3.00.A
     */
    static function loadURL($url, $options = array())
    {
    	// Фечва УРЛ с cUrl наподобяваща функционалност на file_get_contents()
		$ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); 
        $ans = curl_exec($ch);
        curl_close($ch);
    	
        return ($ans);
        
        $default_options = array(
            'method' => 'get',
            'post_data' => FALSE,
            'return_info' => FALSE,
            'return_body' => TRUE,
            'cache' => FALSE,
            'referer' => '',
            'headers' => array(),
            'session' => FALSE,
            'session_close' => FALSE
        );
        
        // Sets the default options.
        foreach ($default_options as $opt => $value) {
            if (!isset($options[$opt]))
            $options[$opt] = $value;
        }
        
        $url_parts = parse_url($url);
        $ch = FALSE;
        $info = array( //Currently only supported by curl.
            'http_code' => 200
        );
        $response = '';
        
        $send_header = array(
            'Accept' => 'text/*',
            'User-Agent' => 'BinGet/1.00.A (http://www.bin-co.com/php/scripts/load/)'
        ) + $options['headers'];     // Add custom headers provided by the user.
        if ($options['cache']) {
            $cache_folder = joinPath(sys_get_temp_dir(), 'php-load-function');
            
            if (isset($options['cache_folder']))
            $cache_folder = $options['cache_folder'];
            
            if (!file_exists($cache_folder)) {
                $old_umask = umask(0);     // Or the folder will not get write permission for everybody.
                mkdir($cache_folder, 0777);
                umask($old_umask);
            }
            
            $cache_file_name = md5($url) . '.cache';
            $cache_file = joinPath($cache_folder, $cache_file_name);     //Don't change the variable name - used at the end of the function.
            if (file_exists($cache_file)) { // Cached file exists - return that.
                $response = file_get_contents($cache_file);
                
                //Seperate header and content
                $separator_position = strpos($response, "\r\n\r\n");
                $header_text = substr($response, 0, $separator_position);
                $body = substr($response, $separator_position + 4);
                
                foreach (explode("\n", $header_text) as $line) {
                    $parts = explode(": ", $line);
                    
                    if (count($parts) == 2)
                    $headers[$parts[0]] = chop($parts[1]);
                }
                $headers['cached'] = TRUE;
                
                if (!$options['return_info'])
                return $body;
                else
                return array(
                    'headers' => $headers,
                    'body' => $body,
                    'info' => array(
                        'cached' => TRUE
                    )
                );
            }
        }
        
        if (isset($options['post_data'])) { //There is an option to specify some data to be posted.
            $options['method'] = 'post';
            
            if (is_array($options['post_data'])) { //The data is in array format.
                $post_data = array();
                
                foreach ($options['post_data'] as $key => $value) {
                    $post_data[] = "$key=" . urlencode($value);
                }
                $url_parts['query'] = implode('&', $post_data);
            } else { //Its a string
                $url_parts['query'] = $options['post_data'];
            }
        } elseif (isset($options['multipart_data'])) { //There is an option to specify some data to be posted.
            $options['method'] = 'post';
            $url_parts['query'] = $options['multipart_data'];
            
            /*
            This array consists of a name-indexed set of options.
            For example,
            'name' => array('option' => value)
            Available options are:
            filename: the name to report when uploading a file.
            type: the mime type of the file being uploaded (not used with curl).
            binary: a flag to tell the other end that the file is being uploaded in binary mode (not used with curl).
            contents: the file contents. More efficient for fsockopen if you already have the file contents.
            fromfile: the file to upload. More efficient for curl if you don't have the file contents.
            
            Note the name of the file specified with fromfile overrides filename when using curl.
            */
        }
        
        ///////////////////////////// Curl /////////////////////////////////////
        //If curl is available, use curl to get the data.
        if (function_exists("curl_init") and (!(isset($options['use']) and $options['use'] == 'fsocketopen'))) { //Don't use curl if it is specifically stated to use fsocketopen in the options
            
            if (isset($options['post_data'])) { //There is an option to specify some data to be posted.
                $page = $url;
                $options['method'] = 'post';
                
                if (is_array($options['post_data'])) { //The data is in array format.
                    $post_data = array();
                    
                    foreach ($options['post_data'] as $key => $value) {
                        $post_data[] = "$key=" . urlencode($value);
                    }
                    $url_parts['query'] = implode('&', $post_data);
                } else { //Its a string
                    $url_parts['query'] = $options['post_data'];
                }
            } else {
                if (isset($options['method']) and $options['method'] == 'post') {
                    $page = $url_parts['scheme'] . '://' . $url_parts['host'] . $url_parts['path'];
                } else {
                    $page = $url;
                }
            }
            
            if ($options['session'] and isset($GLOBALS['_binget_curl_session']))
            $ch = $GLOBALS['_binget_curl_session'];     //Session is stored in a global variable
            else
            $ch = curl_init($url_parts['host']);
            
            curl_setopt($ch, CURLOPT_URL, $page) or die("Invalid cURL Handle Resouce");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);     //Just return the data - not print the whole thing.
            curl_setopt($ch, CURLOPT_HEADER, TRUE);     //We need the headers
            curl_setopt($ch, CURLOPT_NOBODY, !($options['return_body']));     //The content - if TRUE, will not download the contents. There is a ! operation - don't remove it.
            $tmpdir = NULL;     //This acts as a flag for us to clean up temp files
            if (isset($options['method']) and $options['method'] == 'post' and isset($url_parts['query'])) {
                curl_setopt($ch, CURLOPT_POST, TRUE);
                
                if (is_array($url_parts['query'])) {
                    //multipart form data (eg. file upload)
                    $postdata = array();
                    
                    foreach ($url_parts['query'] as $name => $data) {
                        if (isset($data['contents']) && isset($data['filename'])) {
                            if (!isset($tmpdir)) { //If the temporary folder is not specifed - and we want to upload a file, create a temp folder.
                                //  :TODO:
                                $dir = sys_get_temp_dir();
                                $prefix = 'load';
                                
                                if (substr($dir, -1) != '/')
                                $dir .= '/';
                                
                                do {
                                    $path = $dir . $prefix . mt_rand(0, 9999999);
                                } while (!mkdir($path, $mode));
                                
                                $tmpdir = $path;
                            }
                            $tmpfile = $tmpdir . '/' . $data['filename'];
                            file_put_contents($tmpfile, $data['contents']);
                            $data['fromfile'] = $tmpfile;
                        }
                        
                        if (isset($data['fromfile'])) {
                            // Not sure how to pass mime type and/or the 'use binary' flag
                            $postdata[$name] = '@' . $data['fromfile'];
                        } elseif (isset($data['contents'])) {
                            $postdata[$name] = $data['contents'];
                        } else {
                            $postdata[$name] = '';
                        }
                    }
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
                } else {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $url_parts['query']);
                }
            }
            
            //Set the headers our spiders sends
            curl_setopt($ch, CURLOPT_USERAGENT, $send_header['User-Agent']);     //The Name of the UserAgent we will be using ;)
            $custom_headers = array(
                "Accept: " . $send_header['Accept']
            );
            
            if (isset($options['modified_since']))
            array_push($custom_headers, "If-Modified-Since: " . gmdate('D, d M Y H:i:s \G\M\T', strtotime($options['modified_since'])));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $custom_headers);
            
            if ($options['referer'])
            curl_setopt($ch, CURLOPT_REFERER, $options['referer']);
            
            curl_setopt($ch, CURLOPT_COOKIEJAR, "/tmp/binget-cookie.txt");     //If ever needed...
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            
            $custom_headers = array();
            unset($send_header['User-Agent']);     // Already done (above)
            foreach ($send_header as $name => $value) {
                if (is_array($value)) {
                    foreach ($value as $item) {
                        $custom_headers[] = "$name: $item";
                    }
                } else {
                    $custom_headers[] = "$name: $value";
                }
            }
            
            if (isset($url_parts['user']) and isset($url_parts['pass'])) {
                $custom_headers[] = "Authorization: Basic " . base64_encode($url_parts['user'] . ':' . $url_parts['pass']);
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $custom_headers);
            
            $response = curl_exec($ch);
            
            if (isset($tmpdir)) {
                //rmdirr($tmpdir); //Cleanup any temporary files :TODO:
            }
            
            $info = curl_getinfo($ch);     //Some information on the fetch
            if ($options['session'] and !$options['session_close'])
            $GLOBALS['_binget_curl_session'] = $ch;     //Dont close the curl session. We may need it later - save it to a global variable
            else
            curl_close($ch);     //If the session option is not set, close the session.
            //////////////////////////////////////////// FSockOpen //////////////////////////////
        } else { //If there is no curl, use fsocketopen - but keep in mind that most advanced features will be lost with this approch.
            
            if (!isset($url_parts['query']) || (isset($options['method']) and $options['method'] == 'post'))
            $page = $url_parts['path'];
            else
            $page = $url_parts['path'] . '?' . $url_parts['query'];
            
            if (!isset($url_parts['port']))
            $url_parts['port'] = ($url_parts['scheme'] == 'https' ? 443 : 80);
            $host = ($url_parts['scheme'] == 'https' ? 'ssl://' : '') . $url_parts['host'];
            $fp = fsockopen($host, $url_parts['port'], $errno, $errstr, 30);
            
            if ($fp) {
                $out = '';
                
                if (isset($options['method']) and $options['method'] == 'post' and isset($url_parts['query'])) {
                    $out .= "POST $page HTTP/1.1\r\n";
                } else {
                    $out .= "GET $page HTTP/1.0\r\n";     //HTTP/1.0 is much easier to handle than HTTP/1.1
                }
                $out .= "Host: $url_parts[host]\r\n";
                
                foreach ($send_header as $name => $value) {
                    if (is_array($value)) {
                        foreach ($value as $item) {
                            $out .= "$name: $item\r\n";
                        }
                    } else {
                        $out .= "$name: $value\r\n";
                    }
                }
                $out .= "Connection: Close\r\n";
                
                //HTTP Basic Authorization support
                if (isset($url_parts['user']) and isset($url_parts['pass'])) {
                    $out .= "Authorization: Basic " . base64_encode($url_parts['user'] . ':' . $url_parts['pass']) . "\r\n";
                }
                
                //If the request is post - pass the data in a special way.
                if (isset($options['method']) and $options['method'] == 'post') {
                    if (is_array($url_parts['query'])) {
                        //multipart form data (eg. file upload)
                        
                        // Make a random (hopefully unique) identifier for the boundary
                        srand((double) microtime() * 1000000);
                        $boundary = "---------------------------" . substr(md5(rand(0, 32000)), 0, 10);
                        
                        $postdata = array();
                        $postdata[] = '--' . $boundary;
                        
                        foreach ($url_parts['query'] as $name => $data) {
                            $disposition = 'Content-Disposition: form-data; name="' . $name . '"';
                            
                            if (isset($data['filename'])) {
                                $disposition .= '; filename="' . $data['filename'] . '"';
                            }
                            $postdata[] = $disposition;
                            
                            if (isset($data['type'])) {
                                $postdata[] = 'Content-Type: ' . $data['type'];
                            }
                            
                            if (isset($data['binary']) && $data['binary']) {
                                $postdata[] = 'Content-Transfer-Encoding: binary';
                            } else {
                                $postdata[] = '';
                            }
                            
                            if (isset($data['fromfile'])) {
                                $data['contents'] = file_get_contents($data['fromfile']);
                            }
                            
                            if (isset($data['contents'])) {
                                $postdata[] = $data['contents'];
                            } else {
                                $postdata[] = '';
                            }
                            $postdata[] = '--' . $boundary;
                        }
                        $postdata = implode("\r\n", $postdata) . "\r\n";
                        $length = strlen($postdata);
                        $postdata = 'Content-Type: multipart/form-data; boundary=' . $boundary . "\r\n" . 'Content-Length: ' . $length . "\r\n" . "\r\n" . $postdata;
                        
                        $out .= $postdata;
                    } else {
                        $out .= "Content-Type: application/x-www-form-urlencoded\r\n";
                        $out .= 'Content-Length: ' . strlen($url_parts['query']) . "\r\n";
                        $out .= "\r\n" . $url_parts['query'];
                    }
                }
                $out .= "\r\n";
                
                fwrite($fp, $out);
                
                while (!feof($fp)) {
                    $response .= fgets($fp, 128);
                }
                fclose($fp);
            }
        }
        
        //Get the headers in an associative array
        $headers = array();
        
        if ($info['http_code'] == 404) {
            $body = "";
            $headers['Status'] = 404;
        } else {
            //Seperate header and content
            $header_text = substr($response, 0, $info['header_size']);
            $body = substr($response, $info['header_size']);
            
            foreach (explode("\n", $header_text) as $line) {
                $parts = explode(": ", $line);
                
                if (count($parts) == 2) {
                    if (isset($headers[$parts[0]])) {
                        if (is_array($headers[$parts[0]]))
                        $headers[$parts[0]][] = chop($parts[1]);
                        else
                        $headers[$parts[0]] = array(
                            $headers[$parts[0]],
                            chop($parts[1])
                        );
                    } else {
                        $headers[$parts[0]] = chop($parts[1]);
                    }
                }
            }
        }
        
        if (isset($cache_file)) { //Should we cache the URL?
            file_put_contents($cache_file, $response);
        }
        
        if ($options['return_info'])
        return array(
            'headers' => $headers,
            'body' => $body,
            'info' => $info,
            'curl_handle' => $ch
        );
        
        return $body;
    }
    
    
    /**
     * Добавя параметър в стринг представящ URL
     */
    static function addParams($url, $newParams)
    {
        $purl = parse_url($url);
        
        if (!$purl)
        return FALSE;
        
        $params = array();
        
        if (!empty($purl["query"])) {
            parse_str($purl["query"], $params);
        }
        
        // Добавяме новите параметри
        foreach ($newParams as $key => $value) {
            $params[$key] = $value;
        }
        
        $purl["query"] = http_build_query($params);

        $res = "";
        
        if (isset($purl["scheme"])) {
            $res .= $purl["scheme"] . "://";
        }
        
        if (isset($purl["user"])) {
            $res .= $purl["user"];
            $res .= $purl["pass"];
            $res .= "@";
        }
        $res .= $purl["host"];
        
        if ($purl["port"]) {
            $res .= ":" . $purl["port"];
        }
        
        $res .= $purl["path"];
        
        if (isset($purl["query"])) {
            $res .= "?" . $purl["query"];
        }
        
        if (isset($purl["fragment"])) {
            $res .= "#" . $purl["fragment"];
        }
        
        return $res;
    }



    /**
     * Премахва опасни символи от URL адреси
     */
    static function escape($url)
    {
        $url = str_replace(array('&amp;', '<', ' ', '"'), array('&', '&lt', '+', '&quot;'), $url);
        
        $parts = explode(':', $url, 2);

        $scheme = strtolower($parts[0]);

        if(!in_array($scheme, array('http', 'https', 'ftp', 'ftps'))) {
            $scheme = preg_replace('/[^a-z0-9]+/', '', $scheme);
            $url = "javascript:alert('" . tr('Непозволенa URL схема') . ":&quot;{$scheme}&quot;');";
        }
        
       // $url = htmlentities($url, ENT_QUOTES, 'UTF-8');

        return $url;
    }

    
    /**
     * Дали посоченото URL е локално?
     */
    static function isLocal(&$url1, &$rest = NULL)
    {
        $url = $url1;
		
        $httpBoot = getBoot(TRUE);
		
		if (EF_APP_NAME_FIXED !== TRUE) {
            $app = Request::get('App');
            $httpBoot .= '/' . ($app ? $app : EF_APP_NAME);
        }

        $httpBootS = $httpBoot;

        $starts = array("https://", "http://", '//', 'www.');

		$httpBoot = str::removeFromBegin($httpBoot, $starts);

		$url      = str::removeFromBegin($url, $starts);

        if (stripos($url, $httpBoot) === 0) {
            $result = TRUE;
            $rest   = substr($url, strlen($httpBoot));
            $url1 = $httpBootS . $rest;
        } else {
            $result = FALSE;
        }

        return $result;
    }
        
    
    /**
     * Аналогична фунция на urldecode()
     * Прави опити за конвертиране в UTF-8. Ако не успее връща оригиналното URL.
     * 
     * @param URL $url
     * 
     * @return URL
     */
    static function decodeUrl($url)
    {
        // Декодираме URL' то
        $decodedUrl = urldecode($url);
        
        // Проверяваме дали е валиден UTF-8
        if (mb_check_encoding($decodedUrl, 'UTF-8')) {
            
            // Ако е валиден връщаме резултата
            return $decodedUrl;
        }
        
        try {
            
            // Използваме наша функция за конвертиране
            $decodedUrl = i18n_Charset::convertToUtf8($decodedUrl);
        } catch (core_exception_Expect $e) { }
        
        // Проверяваме дали е валиден UTF-8
        if (mb_check_encoding($decodedUrl, 'UTF-8')) {
            
            // Ако е валиден връщаме резултата
            return $decodedUrl;
        }
        
        // Ако все още не е валидно URL, връщаме оригиналното
        return $url;
    }
    

    /**
     * Функция, която връща масив с намерените уеб-адреси
     * 
     * Допускат са само прости уеб-адреси
     */
    static function extractWebAddress($line)
    {
        preg_match_all("/(((http(s?)):\/\/)|(www\.))([\%\_\-\.a-zA-Z0-9]+)/", $line, $matches);
        
        if(count($matches[0])) {
            foreach($matches[0] as $id => &$w) {
                if(!self::isValidTld($w)) {
                    unset($matches[0][$id]);
                    continue;
                }

                if(strpos($w, 'http://www.') === 0) {
                    $w = substr($w, strlen('http://'));
                } elseif(strpos($w, 'https://www.') === 0) {
                    $w = substr($w, strlen('https://'));
                }
            }
        }

        return $matches[0];
    }

    
    /**
     * Подготвяме масив с валидни TLD от файл
     */
    static public function prepareValideTldArray()
    {
    	$url = 'http://data.iana.org/TLD/tlds-alpha-by-domain.txt';
	    $pageSource = file_get_contents($url);
	    
		if (!$pageSource) {
		    echo "ERROR: Не може да се вземе съдържанието<br />\n";
		} else {
			$text = "UTC";
			
			$source = stristr($pageSource, "UTC");
			$source = substr($source, strpos($source, $text)+3);
			$source = mb_strtolower($source);
			
			$source = str_replace("\n", "', ", trim($source));
			$source = str_replace("', ", "', '",trim($source));
			$source = "'" . $source  . "'";
			
			$valideTld = explode(",", $source);
			
			return $valideTld;
		}
    }


    /**
     * Проверява дали е валидно домейн името
     */
    public static function isValidDomainName($domain)
    {
        return (preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $domain) //valid chars check
            && preg_match("/^.{1,253}$/", $domain) //overall length check
            && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $domain)   ); //length of each label
    }
}