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
     * Масив с позволените стойности на параметрите за различните
     * Open Graph Protocol обекти
     */
    protected static $allowed = array(
    'Default' => array(
        'locale',
        'sitename',
        'description',
        'title',
        'type',
        'url',
        'determiner',),
    'Audio' => array(
        'url',
        'secureurl',
        'type',),
    'Video' => array(
        'url',
        'secureurl',
        'type',
        'height',
        'width',),
    'Article' => array(
        'published',
        'modified',
        'expiration',),
    'Profile' => array(
        'firstname',
        'lastname',
        'username',
        'gender'),
    'VideoEpisode' => array('series'),
    'Book' => array('isbn','releasedate'),
    'VideoObject' => array('releasedate','duration'),
    'Image' => array(
        'url',
        'secureurl',
        'type',
        'height',
        'width'));
    
    
    /**
     *  По подразбиране връща OpenGraphProtocol обект, Ако е зададен стринг
     *  се връща съответния OpenGraphProtocolObject
     *  @param array $params с какви параметри искаме да е обекта
     *  @param string $str какъв обект искаме, по подразбиране NULL
     *  @return OpenGraphProtocol $ogp
     */
    public static function get($params = array(), $str = null)
    {
        // Ако не е посочен тип връщаме стандартния OpenGraphProtocol обект
        if ($str === null) {
            $ogp = new OpenGraphProtocol();
            $allowed = static::$allowed['Default'];
            foreach ($params as $key => $value) {
                expect(in_array(strtolower($key), $allowed), 'Невалиден параметър');
                $method = "set{$key}";
                $ogp->$method($value);
            }
            
            return $ogp;
        }
        
        // Ако има подаден стринг, го преобразураме
        $str = strtolower($str);
        $method = "get{$str}";
        
        // Трябва да съществува метод за създаването на този обект
        expect(method_exists('ograph_Factory', $method), "Не се поддържа обекта {$str} от Open Graph Protocol");
        
        // Извикваме метода за генериране на обекта
        $ogp = call_user_func_array("static::{$method}", array($params));
        
        return $ogp;
    }
    
    
    /**
     * Връщаме нов Аудио обект
     * @param  array                  $params
     *                                        $params['Url'] - Адрес
     *                                        $params['secureUrl'] - Защитен адрес
     *                                        $params['Type'] - Разширение
     * @return OpenGraphProtocolAudio $ogp
     */
    public static function getAudio($params = array())
    {
        $allowed = static::$allowed['Audio'];
        $ogp = new OpenGraphProtocolAudio();
        foreach ($params as $key => $value) {
            expect(in_array(strtolower($key), $allowed), "Аудио обекта не поддържа параметър {$key}");
            $method = "set{$key}";
            $ogp->$method($value);
        }
        
        return $ogp;
    }
    
    
    /**
     * Връщаме нов Article обект
     * @param  array                    $params параметри
     *                                          $params['Published'] - Дата на публикуване
     *                                          $params['Modified'] - Последна редакция
     *                                          $params['Expiration'] - Дата на изтичане
     * @return OpenGraphProtocolArticle $ogp
     */
    public static function getArticle($params = array())
    {
        $allowed = static::$allowed['Article'];
        $ogp = new OpenGraphProtocolArticle();
        foreach ($params as $key => $value) {
            expect(in_array(strtolower($key), $allowed), "Article обекта не поддържа параметър {$key}");
            $method = "set{$key}Time";
            $ogp->$method($value);
        }
        
        return $ogp;
    }
    
    
    /**
     * Връщаме нов Профил обект
     * @param  array                    $params параметри
     *                                          $params['FirstName'] - Малко име
     *                                          $params['LastName'] - Последно име
     *                                          $params['Username'] - Потребителско име
     *                                          $params['Gender'] - Пол
     * @return OpenGraphProtocolProfile $ogp
     */
    public static function getProfile($params = array())
    {
        $allowed = static::$allowed['Profile'];
        $ogp = new OpenGraphProtocolProfile();
        foreach ($params as $key => $value) {
            expect(in_array(strtolower($key), $allowed), "Profile обекта не поддържа параметър {$key}");
            $method = "set{$key}";
            $ogp->$method($value);
        }
        
        return $ogp;
    }
    
    
    /**
     * Връщаме нов Book обект
     * @param  array                 $params параметри
     *                                       $params['Isbn'] - ISBN
     *                                       $params['ReleaseDate'] - Дата на пускане
     * @return OpenGraphProtocolBook $ogp
     */
    public static function getBook($params = array())
    {
        $allowed = static::$allowed['Book'];
        $ogp = new OpenGraphProtocolBook();
        foreach ($params as $key => $value) {
            expect(in_array(strtolower($key), $allowed), "Book обекта не поддържа параметър {$key}");
            $method = "set{$key}";
            $ogp->$method($value);
        }
        
        return $ogp;
    }
    
    
    /**
     * Връщаме нов Видео обект
     * @param  array                  $params
     *                                        $params['Url'] - Адрес
     *                                        $params['secureUrl'] - Защитен адрес
     *                                        $params['Type'] string mime-type
     *                                        $params['Height'] int
     *                                        $params['Width']	 int
     * @return OpenGraphProtocolVideo $ogp
     */
    public static function getVideo($params = array())
    {
        $allowed = static::$allowed['Video'];
        $ogp = new OpenGraphProtocolVideo();
        foreach ($params as $key => $value) {
            expect(in_array(strtolower($key), $allowed), "Video обекта не поддържа параметър {$key}");
            $method = "set{$key}";
            $ogp->$method($value);
        }
        
        return $ogp;
    }
    
    
    /**
     * Връщаме нов Видео обект
     * @param  array                        $params параметри
     *                                              $params['ReleaseDate'] - Дата на пускане
     *                                              $params['Duration'] - Продължителност
     * @return OpenGraphProtocolVideoObject $ogp
     */
    public static function getVideoObject($params = array())
    {
        $allowed = static::$allowed['VideoObject'];
        $ogp = new OpenGraphProtocolVideoObject();
        foreach ($params as $key => $value) {
            expect(in_array(strtolower($key), $allowed), "Video обекта не поддържа параметър {$key}");
            $method = "set{$key}";
            $ogp->$method($value);
        }
        
        return $ogp;
    }
    
    
    /**
     * Връщаме нов Видео Епизод обект
     * @param  array                  $params
     *                                        $params['Series']
     * @return OpenGraphProtocolAudio $ogp
     */
    public static function getVideoepisode($params = array())
    {
        $allowed = static::$allowed['VideoEpisode'];
        $ogp = new OpenGraphProtocolVideoEpisode();
        expect(in_array('series', $allowed), 'Video Episode обекта не поддържа параметъра');
        $ogp->setSeries($params['Series']);
        
        return $ogp;
    }
    
    
    /**
     * Връщаме нов Image обект
     * @param  array                  $params
     *                                        $params['Url'] - Адрес
     *                                        $params['secureUrl'] - Защитен адрес
     *                                        $params['Type'] string mime-type
     *                                        $params['Height'] int
     *                                        $params['Width']	 int
     * @return OpenGraphProtocolImage $ogp
     */
    public static function getImage($params = array())
    {
        $allowed = static::$allowed['Image'];
        $ogp = new OpenGraphProtocolImage();
        foreach ($params as $key => $value) {
            expect(in_array(strtolower($key), $allowed), "Image обекта не поддържа параметър {$key}");
            $method = "set{$key}";
            $ogp->$method($value);
        }
        
        return $ogp;
    }
    
    
    /**
     * Инстанцираме и генерираме Ографа по зададените данни
     * Enter description here ...
     * @param  stdClass $data
     * @return core_ET  $tpl
     */
    public static function generateOgraph($data)
    {
        $meta = '';
        $tpl = new ET('');
        
        // OGP обект съдържащ информацията за сайта
        $ogp = static::get($data->siteInfo);
        
        // Изображението което ще се показва в OGP
        if ($data->imageInfo) {
            $image = static::getImage($data->imageInfo);
            $ogp->addImage($image);
        }
        $meta .= "\n{$ogp->toHTML()}";
        if ($data->recInfo) {
            
            // Ако има допълнителен обект то ние го генерираме и него
            $type = $data->siteInfo['Type'];
            $method = 'get' . $type;
            $ogpRec = static::$method($data->recInfo);
            
            $meta .= "\n{$ogpRec->toHTML()}";
        }
        
        $tpl->append('prefix="og: http://ogp.me/ns#"', 'OG_PREFIX');
        $tpl->append($meta, 'META_OGRAPH');
        
        // Връщаме готовите мета тагове
        return $tpl;
    }
}
