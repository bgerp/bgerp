<?php



/**
 * Клас 'core_Url' ['url'] - Функции за за работа със URL
 *
 *
 * @category  all
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Url
{
    
    
    /**
     * @todo Чака за документация...
     */
    function parseUrl(&$url)
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
                $parts['query_params'][$sKey] = urldecode($sValue);
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
            setIfNot($parts, pathInfo(urldecode($parts['path'])));
        }
        
        // От http://data.iana.org/TLD/tlds-alpha-by-domain.txt
        $valideTld = array(
            'ac', 'ad', 'ae', 'aero', 'af', 'ag', 'ai', 'al', 'am', 'an', 'ao', 'aq',
            'ar', 'arpa', 'as', 'asia', 'at', 'au', 'aw', 'ax', 'az', 'ba', 'bb', 'bd',
            'be', 'bf', 'bg', 'bh', 'bi', 'biz', 'bj', 'bm', 'bn', 'bo', 'br', 'bs',
            'bt', 'bv', 'bw', 'by', 'bz', 'ca', 'cat', 'cc', 'cd', 'cf', 'cg', 'ch',
            'ci', 'ck', 'cl', 'cm', 'cn', 'co', 'com', 'coop', 'cr', 'cu', 'cv', 'cx',
            'cy', 'cz', 'de', 'dj', 'dk', 'dm', 'do', 'dz', 'ec', 'edu', 'ee', 'eg',
            'er', 'es', 'et', 'eu', 'fi', 'fj', 'fk', 'fm', 'fo', 'fr', 'ga', 'gb',
            'gd', 'ge', 'gf', 'gg', 'gh', 'gi', 'gl', 'gm', 'gn', 'gov', 'gp', 'gq',
            'gr', 'gs', 'gt', 'gu', 'gw', 'gy', 'hk', 'hm', 'hn', 'hr', 'ht', 'hu',
            'id', 'ie', 'il', 'im', 'in', 'info', 'int', 'io', 'iq', 'ir', 'is', 'it',
            'je', 'jm', 'jo', 'jobs', 'jp', 'ke', 'kg', 'kh', 'ki', 'km', 'kn', 'kp',
            'kr', 'kw', 'ky', 'kz', 'la', 'lb', 'lc', 'li', 'lk', 'lr', 'ls', 'lt',
            'lu', 'lv', 'ly', 'ma', 'mc', 'md', 'me', 'mg', 'mh', 'mil', 'mk', 'ml',
            'mm', 'mn', 'mo', 'mobi', 'mp', 'mq', 'mr', 'ms', 'mt', 'mu', 'museum',
            'mv', 'mw', 'mx', 'my', 'mz', 'na', 'name', 'nc', 'ne', 'net', 'nf', 'ng',
            'ni', 'nl', 'no', 'np', 'nr', 'nu', 'nz', 'om', 'org', 'pa', 'pe', 'pf',
            'pg', 'ph', 'pk', 'pl', 'pm', 'pn', 'pr', 'pro', 'ps', 'pt', 'pw', 'py',
            'qa', 're', 'ro', 'rs', 'ru', 'rw', 'sa', 'sb', 'sc', 'sd', 'se', 'sg', 'sh',
            'si', 'sj', 'sk', 'sl', 'sm', 'sn', 'so', 'sr', 'st', 'su', 'sv', 'sy', 'sz',
            'tc', 'td', 'tel', 'tf', 'tg', 'th', 'tj', 'tk', 'tl', 'tm', 'tn', 'to',
            'tp', 'tr', 'travel', 'tt', 'tv', 'tw', 'tz', 'ua', 'ug', 'uk', 'us', 'uy',
            'uz', 'va', 'vc', 've', 'vg', 'vi', 'vn', 'vu', 'wf', 'ws', 'xn--0zwm56d',
            'xn--11b5bs3a9aj6g', 'xn--80akhbyknj4f', 'xn--9t4b11yi5a', 'xn--deba0ad',
            'xn--fiqs8s', 'xn--fiqz9s', 'xn--fzc2c9e2c', 'xn--g6w251d', 'xn--kgbechtv',
            'xn--hgbk6aj7f53bba', 'xn--hlcj6aya9esc7a', 'xn--j6w193g', 'xn--jxalpdlp',
            'xn--kprw13d', 'xn--kpry57d', 'xn--mgbaam7a8h', 'xn--mgbayh7gpa',
            'xn--mgberp4a5d4ar', 'xn--o3cw4h', 'xn--p1ai', 'xn--pgbs0dh', 'xn--wgbh1c',
            'xn--xkc2al3hye2a', 'xn--ygbi2ammx', 'xn--zckzah', 'ye', 'yt', 'za', 'zm', 'zw');
        
        if (!core_URL::isValidUrl($url)) {
            $parts['error'] = "Невалидно URL";
        } elseif ($parts['tld'] && !in_array($parts['tld'], $valideTld)) {
            $parts['error'] = "Невалидно разширение на домейн|*: <b>" . $parts['tld'] . "</b>";
        }
        
        return $parts;
    }
    
    
    /**
     * Проверява дали дадено URL е валидно
     */
    function isValidUrl2($url)
    {
        // схема 
        $urlregex = "^([a-z0-9+-._]+)\:\/\/";
        
        // USER и PASS (опционално)
        $urlregex .= "([a-z0-9+!*(),;?&=\$_.-]+(\:[a-z0-9+!*(),;?&=\$_.-]+)?@)?";
        
        // HOSTNAME или IP
        $urlregex .= "[a-z0-9+\$_-]+(\.[a-z0-9+\$_-]+)*";    // http://x = allowed (ex. http://localhost, http://routerlogin)
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
    function isValidUrl($url, $absolute = TRUE)
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
        return file_get_contents($url);
        
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
        ) + $options['headers'];    // Add custom headers provided by the user.
        if ($options['cache']) {
            $cache_folder = joinPath(sys_get_temp_dir(), 'php-load-function');
            
            if (isset($options['cache_folder']))
            $cache_folder = $options['cache_folder'];
            
            if (!file_exists($cache_folder)) {
                $old_umask = umask(0);    // Or the folder will not get write permission for everybody.
                mkdir($cache_folder, 0777);
                umask($old_umask);
            }
            
            $cache_file_name = md5($url) . '.cache';
            $cache_file = joinPath($cache_folder, $cache_file_name);    //Don't change the variable name - used at the end of the function.
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
            $ch = $GLOBALS['_binget_curl_session'];    //Session is stored in a global variable
            else
            $ch = curl_init($url_parts['host']);
            
            curl_setopt($ch, CURLOPT_URL, $page) or die("Invalid cURL Handle Resouce");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);    //Just return the data - not print the whole thing.
            curl_setopt($ch, CURLOPT_HEADER, TRUE);    //We need the headers
            curl_setopt($ch, CURLOPT_NOBODY, !($options['return_body']));    //The content - if TRUE, will not download the contents. There is a ! operation - don't remove it.
            $tmpdir = NULL;    //This acts as a flag for us to clean up temp files
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
            curl_setopt($ch, CURLOPT_USERAGENT, $send_header['User-Agent']);    //The Name of the UserAgent we will be using ;)
            $custom_headers = array(
                "Accept: " . $send_header['Accept']
            );
            
            if (isset($options['modified_since']))
            array_push($custom_headers, "If-Modified-Since: " . gmdate('D, d M Y H:i:s \G\M\T', strtotime($options['modified_since'])));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $custom_headers);
            
            if ($options['referer'])
            curl_setopt($ch, CURLOPT_REFERER, $options['referer']);
            
            curl_setopt($ch, CURLOPT_COOKIEJAR, "/tmp/binget-cookie.txt");    //If ever needed...
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            
            $custom_headers = array();
            unset($send_header['User-Agent']);    // Already done (above)
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
            
            $info = curl_getinfo($ch);    //Some information on the fetch
            if ($options['session'] and !$options['session_close'])
            $GLOBALS['_binget_curl_session'] = $ch;    //Dont close the curl session. We may need it later - save it to a global variable
            else
            curl_close($ch);    //If the session option is not set, close the session.
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
                    $out .= "GET $page HTTP/1.0\r\n";    //HTTP/1.0 is much easier to handle than HTTP/1.1
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
        
        $purl["query"] = "";
        
        foreach ($params as $name => $value) {
            if (is_array($value)) {
                foreach ($value as $key => $v) {
                    $purl["query"] .= ($purl["query"] ? '&' : '') . "{$name}[{$key}]=" . urlencode($v);
                }
            } else {
                $purl["query"] .= ($purl["query"] ? '&' : '') . "{$name}=" . urlencode($value);
            }
        }
        
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
}