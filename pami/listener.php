<?php
require_once $argv[6];

$pamiClientOptions = array(
        'host' => $argv[2],
        'scheme' => $argv[1] . '://',
        'port' => $argv[3],
        'username' => $argv[4],
        'secret' => $argv[5],
        'connect_timeout' => 10000,
        'read_timeout' => 10000
);

$pamiClient = new PAMI\Client\Impl\ClientImpl($pamiClientOptions);

// Open the connection
$pamiClient->open();

$url = rtrim($argv[7], '?');
$url = rtrim($url, '/');
$url .= '/?';

$listener = new MyListener();
$listener->setUrl($url);
$listener->setKey($argv[8]);

$pamiClient->registerEventListener(array($listener, 'handlerMethod'));

while(true) {
    $pamiClient->process();
    usleep(1000);
}

$pamiClient->close();


/**
 * 
 * @author yusein
 */
class MyListener
{
    
    /**
     * 
     * @var string
     */
    private $url = '';
    
    
    
    /**
     * 
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }
    
    
    /**
     * 
     * @return string
     */
    public function getUrl()
    {
        
        return $this->url;
    }
    
    /**
     *
     * @var string
     */
    private $key = '';
    
    
    
    /**
     *
     * @param string $url
     */
    public function setKey($key)
    {
        $this->key = $key;
    }
    
    
    /**
     *
     * @return string
     */
    public function getKey()
    {
        
        return $this->key;
    }
    
    
    /**
     * Прихваща извикването и прави заявка
     * 
     * @param stdClass $event
     */
    public function handlerMethod($event)
    {
        $url = $this->getUrl();
        
        $eArr = (array) $event;
        $eArr['__clsName'] = get_class($event);
        
        $url .= http_build_query(array('k' => $this->getKey(), 'data' => base64_encode(gzcompress(serialize($eArr)))));
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT,1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $ans = curl_exec($ch);
        curl_close($ch);
        
    }
}
