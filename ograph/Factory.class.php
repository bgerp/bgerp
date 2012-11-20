<?php 
require_once getFullPath('ograph/open-graph-protocol-tools/media.php');
require_once getFullPath('ograph/open-graph-protocol-tools/objects.php');

/**
 * Фактори клас за генериране на Open Graph Protocol елементи
 *
 *
 * @category  vendors
 * @package   ograph
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class ograph_Factory extends core_Master
{
	/**
     *  Връща OpenGraphProtocol обект, взависимост от подадения стринг
     *  Ако не е посочен тип, връща обикновения OpenGraphProtocol
     *  @param string $str какъв обект искаме, по подразбиране NULL
     *  @return OpenGraphProtocol $ogp 
     */
    static function get($params = array(), $str = NULL)
    {
    	// Ако не е посочен тип връщаме стандартния OpenGraphProtocol обект
        if($str === NULL) {
        	$ogp = new OpenGraphProtocol();
        	
        	// Масив с позволени атрибути
        	$allowed = array('locale',
        					 'sitename',
        					 'title',
        					 'description',
        					 'type',
        					 'url',
        					 'determiner');
        	
        	foreach($params as $key => $value) {
        		expect(in_array(strtolower($key), $allowed), "Невалиден параметър");
	    		$method = "set{$key}";
	    		$ogp->$method($value);
    		}
    		
        	return $ogp;
        } 
		
        // преобразуваме подадения стринг
        $str = strtolower($str);
        $method = "get{$str}";
        
        // Трябва да съществува метод за създаването на този обект
        expect(method_exists('ograph_Factory', $method), "Не се поддържа обекта {$str} от Open Graph Protocol");
        
        // Извикваме метода за генериране на обекта
        $ogp = call_user_func_array("static::{$method}",array($params));
        
        // Връщаме обекта
        return $ogp;
    }
    
    
    /**
     * Връщаме нов Аудио обект
     * @param array $params
     * $params['Url'] - Адрес
     * $params['secureUrl'] - Защитен адрес
     * $params['Type'] - Разширение
     * @return OpenGraphProtocolAudio $vid
     */
	static function getAudio($params = array())
    {
    	$allowed = array('url',
        				 'secureurl',
        				 'title',
        				 'type',);
    	
    	$vid = new OpenGraphProtocolAudio();
    	foreach($params as $key => $value) {
    		expect(in_array(strtolower($key), $allowed), "Аудио обекта неподдържа параметър {$key}");
    		$method = "set{$key}";
    		$vid->$method($value);
    	}  
    	
    	return $vid;
    }
    
    
    /**
     * Връщаме нов Article обект
     * @param array $params параметри
     * $params['Published'] - Дата на публикуване
     * $params['Modified'] - Последна редакция
     * $params['Expiration'] - Дата на изтичане
     * @return OpenGraphProtocolArticle $vid
     */
	static function getArticle($params = array())
    {
    	$allowed = array('published',
        				 'modified',
        				 'expiration',);
    	
    	$vid = new OpenGraphProtocolArticle(); 
    	foreach($params as $key => $value) {
    		expect(in_array(strtolower($key), $allowed), "Article обекта неподдържа параметър {$key}");
    		$method = "set{$key}Time";
    		$vid->$method($value);
    	}
    	
    	return $vid;
    }
    
    
    /**
     * Връщаме нов Профил обект
     * @param array $params параметри
     * $params['FirstName'] - Малко име
     * $params['LastName'] - Последно име
     * $params['Username'] - Потребителско име
     * $params['Gender'] - Пол
     * @return OpenGraphProtocolProfile $vid
     */
	static function getProfile($params = array())
    {
    	$allowed = array('firstname',
        				 'lastname',
        				 'username',
    					 'gender');
    	
    	$vid = new OpenGraphProtocolProfile(); 
    	foreach($params as $key => $value) {
    		expect(in_array(strtolower($key), $allowed), "Profile обекта неподдържа параметър {$key}");
    		$method = "set{$key}";
    		$vid->$method($value);
    	}
    	
    	return $vid;
    }
    
    
    /**
     * Връщаме нов Book обект
     * @param array $params параметри
     * $params['Isbn'] - ISBN
     * $params['ReleaseDate'] - Дата на пускане
     * @return OpenGraphProtocolBook $vid
     */
	static function getBook($params = array())
    {
    	$allowed = array('isbn',
        				 'releasedate',);
    	
    	$vid = new OpenGraphProtocolBook();
    	foreach($params as $key => $value) {
    		expect(in_array(strtolower($key), $allowed), "Book обекта неподдържа параметър {$key}");
    		$method = "set{$key}";
    		$vid->$method($value);
    	} 
    	
    	return $vid;
    }
    
    
    /**
     * Връщаме нов Видео обект
     * @param array $params
     * $params['Url'] - Адрес
     * $params['secureUrl'] - Защитен адрес
     * $params['Type] - Разширение
     * $params['Height] - Височина
     * $params['Width] - Ширина
     * @return OpenGraphProtocolVideo $vid
     */
	static function getVideo($params = array())
    {
    	$allowed = array('url',
        				 'secureurl',
    					 'type',
    					 'height',
    					 'width',);
    	
    	$vid = new OpenGraphProtocolVideo();
    	foreach($params as $key => $value) {
    		expect(in_array(strtolower($key), $allowed), "Video обекта неподдържа параметър {$key}");
    		$method = "set{$key}";
    		$vid->$method($value);
    	} 
    	
    	return $vid;
    }
    
    
    /**
     * Връщаме нов Видео обект
     * @param array $params параметри
     * $params['ReleaseDate'] - Дата на пускане
     * $params['Duration'] - Продължителност
     * @return OpenGraphProtocolVideoObject $vid
     */
    static function getVideoObject($params = array())
    {
    	$allowed = array('releasedate',
        				 'duration',);
    	
    	$vid = new OpenGraphProtocolVideoObject(); 
    	foreach($params as $key => $value) {
    		expect(in_array(strtolower($key), $allowed), "Video обекта неподдържа параметър {$key}");
    		$method = "set{$key}";
    		$vid->$method($value);
    	}
    	
    	return $vid;
    }
    
    
    /**
     * Връщаме нов Видео Епизод обект
     * @param array $params
     * $params['Series']
     * @return OpenGraphProtocolAudio $vid
     */
	static function getVideoepisode($params = array())
    {
    	$allowed = array('series',);
    	
    	$vid = new OpenGraphProtocolVideoEpisode();
    	expect(in_array('series', $allowed), "Video Episode обекта неподдържа параметъра");
    	$vid->setSeries($params['Series']);
    	
    	return $vid;
    }
    
    
    /**
     * Връщаме нов Image обект
     * @param array $params
     * $params['Url'] - Адрес
     * $params['secureUrl'] - Защитен адрес
     * $params['Type']
     * $params['Height']
     * $params['Width']
     * @return OpenGraphProtocolImage $vid
     */
	static function getImage($params = array())
    {
    	$allowed = array('url',
        				 'secureurl',
    					 'type',
    					 'height',
    					 'width',);
    	
    	$vid = new OpenGraphProtocolImage(); 
    	foreach($params as $key => $value) {
    		expect(in_array(strtolower($key), $allowed), "Image обекта неподдържа параметър {$key}");
    		$method = "set{$key}";
    		$vid->$method($value);
    	} 
    	
    	return $vid;
    }
}
