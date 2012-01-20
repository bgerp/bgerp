<?php


/**
 * @todo Чака за документация...
 */
defIfNot('AVATAR_DIR', EF_DOWNLOAD_DIR . '/' . 'AVATAR');


/**
 * @todo Чака за документация...
 */
defIfNot('AVATAR_URL', EF_DOWNLOAD_ROOT . '/' . 'AVATAR');


/**
 * Клас 'avatar_Plugin' -
 *
 *
 * @category  vendors
 * @package   avatar
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class avatar_Plugin extends core_Plugin
{
    
    /**
     * Извиква се след описанието на модела
     */
    function on_AfterDescription(&$mvc)
    {
        if(!$mvc->fields['avatar']) {
            $mvc->FLD('avatar', 'fileman_FileType(bucket=Avatars)', 'caption=Аватар');
        }
    }
    
    
    /**
     * Извиква се преди извличането на вербална стойност за поле от запис
     */
    function on_BeforeGetVerbal($mvc, &$avatar, $rec, $field)
    {
        if($field == 'avatar') {
            $avatar = self::getImg($rec->id);
            
            return FALSE;
        }
    }
    
    
    /**
     * Извиква се след поготовката на колоните ($data->listFields)
     */
    function on_AfterPrepareListFields($mvc, $data)
    {
        $data->listFields = $this->insertAfter($data->listFields, 'id', 'avatar', 'Аватар');
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function insertAfter($sourceArr, $afterField, $key, $value)
    {
        foreach($sourceArr as $k => $v) {
            $destArr[$k] = $v;
            
            if($k == $afterField) {
                $destArr[$key] = $value;
            }
        }
        
        return $destArr;
    }
    
    
    /**
     * Връща html <img> елемент, отговарящ на аватара на потребителя
     */
    static function getImg($userId, $email = NULL, $width = 100)
    {
        if($userId < 0) {
            // Ако става дума за системния потребител
                        $imgLink = sbf('img/100/system.png', '');
        } elseif($userId > 0) {
            // Ако се търси аватара на потребител на системата
                        $userRec = core_Users::fetch($userId);
            
            if($userRec->avatar) {
                $key = md5($userId . "@/@" . EF_SALT) . "_{$width}.png";
                $attr['baseName'] = $key;
                $Thumbnail = cls::get('thumbnail_Thumbnail');
                $imgLink = $Thumbnail->getLink($userRec->avatar, array($width, round($width * 1.5)), &$attr);
            } else {
                $imgLink = avatar_Gravatar::getLink($userRec->email, $width);
            }
        } elseif($email = strtolower(trim($email))) {
            $imgLink = avatar_Gravatar::getLink($email, $width);
        }
        
        if(!$imgLink) {
            $imgLink = sbf('img/100/noavatar.png', '');
        }
        
        $attr['width'] = $width;
        $attr['src']   = $imgLink;
        unset($attr['baseName']);
        
        $img = ht::createElement('img', $attr);
        
        return $img;
    }
}