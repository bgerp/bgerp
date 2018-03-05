<?php



/**
 * Мениджър на чат клиент към prosody - https://github.com/snowblindroan/mod_admin_rest
 *
 *
 * @category  bgerp
 * @package   prosody
 * @author    Dimitar Minekov <mitko@experta.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */



class prosody_RestApi {
    

    /**
     * Създава заявка и връща резултати
     *
     * @param   string  $type   http метод
     * @param   string  $endpoint   API суфикс
     * @param   array   $params Параметри
     * @return  array|false Масив с данни или грешка
     */
    private static function doRequest($type, $endpoint, $params=array())
    {
        expect($conf = core_Packs::getConfig('prosody'));
        
        if (!empty($params)) {
            $data = json_encode($params);
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $conf->PROSODY_ADMIN_URL . '/' . $endpoint);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // timeout after 10 seconds
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        
        $headers = array(
            'Content-Type: application/json',
            'Authorization: Basic '. base64_encode($conf->PROSODY_ADMIN_USER . ":" . $conf->PROSODY_ADMIN_PASS)
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        
        expect(in_array($type, array('GET', 'POST', 'PUT', 'PATCH', 'DELETE')));

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
         
        $result=curl_exec ($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);   //get status code
        curl_close ($ch);
        
        return array('status' => $status_code, 'message' => $result);
    }
    
    
    /**
     * Изпраща съобщение до потребител 
     *
     * @param $user
     * @param $message
     * @return $res: 201 - offline msg, 200 - OK, 404 - no user 
     */
    public static function sendMessage($user, $message)
    {
        $endpoint = 'message' . '/' . $user;
        $type = 'POST';
        $res = self::doRequest($type, $endpoint, array('message' => $message));
        
        return $res;
    }
    
    
    /**
     * Добавя потребител 
     *
     * @param $user
     * @param $roster - име на потребител
     * @return $res: 201 - OK, 409 - user exist
     */
     public static function addUser($user, $password)
     {
        $endpoint = 'user' . '/' . $user;
        
        $res = self::doRequest('POST', $endpoint, array("password" => $password));

        return $res;
    }


    /**
     * Променя паролата на потребителя
     *
     * @param $user - име на потребител
     * @param $password - новата парола
     * @return $res: 201 - OK, 409 - user exist
     */
     public static function changePassword($user, $password)
     {
        $endpoint = 'user' . '/' . $user . '/password';
        
        $res = self::doRequest('PATCH', $endpoint, array("password" => $password));

        return $res;
    }

     
    /**
     * Изтрива потребител
     *
     * @param $user
     * @param $roster - име на потребител
     * @return $res: 200 - OK, 404 - no user
     */
    public static function removeUser($user)
    {
        $endpoint = 'user' . '/' . $user;
    
        $res = self::doRequest('DELETE', $endpoint);
    
        return $res;
    }
    

    /**
     * Добавя контакт на потребител 
     *
     * @param $user
     * @param $roster - име на потребител
     * @return $res: 200 - OK, 404 - no user 
     */
     public static function addRoster($user, $contact)
     {
        $domain = core_Packs::getConfigKey('prosody', 'PROSODY_DOMAIN');
        $endpoint = 'roster' . '/' . $user;
        $type = 'POST';
        if (strpos($contact, "@") === FALSE ) {
            $contact .= "@" . $domain;
        }
        
        $res = self::doRequest($type, $endpoint, array("contact" => $contact));

        return $res;
    }
    
    /**
     * Изтрива контакт от потребител 
     *
     * @param $user
     * @param $roster - име на потребител
     * @return $res: 200 - OK, 404 - no user 
     */
     public static function deleteRoster($user, $contact)
    {
        $domain = core_Packs::getConfigKey('prosody', 'PROSODY_DOMAIN');
        $endpoint = 'roster' . '/' . $user;
        if (strpos($contact, "@") === FALSE ) {
            $contact .= "@" . $domain;
        }
        
        $res = self::doRequest("DELETE", $endpoint, array("contact" => $contact));
        
        return $res;
    }
    
    
    /**
     * Взима списък на потребител 
     *
     * @param $user
     * @return $res: 200 - OK, 404 - no user 
     */
     public static function getRoster($user)
     {
        $endpoint = 'roster' . '/' . $user;
        $type = 'GET';
        $res = self::doRequest($type, $endpoint);

        return $res;
     }
    
    
    /**
     * @param string $user
     * @return
     */
    public static function getConnectedUsers()
    {
        $endpoint = 'users';
        $type = 'GET';
        
        $res = self::doRequest($type, $endpoint);
        
        return $res;
    }
    
}
