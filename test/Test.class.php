<?php
// https://github.com/snowblindroan/mod_admin_rest


DEFINE('PROSODY_DOMAIN','jabber.bags.bg');
DEFINE('PROSODY_ADMIN_URL', "http://jabber.bags.bg:5280/admin_rest");
DEFINE('PROSODY_ADMIN_USER', "dimitar_minekov@jabber.bags.bg");
DEFINE('PROSODY_ADMIN_PASS', 'xmpppass');


class test_Test extends core_Manager {
    

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
        if (!empty($params)) {
            $data = json_encode($params);
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, PROSODY_ADMIN_URL . '/' . $endpoint);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // timeout after 10 seconds
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        
        $headers = array(
            'Content-Type: application/json',
            'Authorization: Basic '. base64_encode(PROSODY_ADMIN_USER . ":" . PROSODY_ADMIN_PASS)
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        
        switch ($type) {
            case 'POST':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                break;
            case 'GET':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                break;
        }
        
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
        $endpoint = 'roster' . '/' . $user;
        $type = 'POST';
        if (strpos($contact, "@") === FLASE ) {
            $contact .= "@" . PROSODY_DOMAIN;
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
        $endpoint = 'roster' . '/' . $user;
        $type = 'DELETE';
        if (strpos($contact, "@") === FLASE ) {
            $contact .= "@" . PROSODY_DOMAIN;
        }
        
        $res = self::doRequest($type, $endpoint, array("contact" => $contact));
        
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
    

    function act_Test()
    {
        self::getConnectedUsers();
        
        //$res .= "Delete roster result: " . self::deleteRoster("dimitar_minekov");
        $res .= "<br>";
       // $res .= self::getRoster("dimitar_minekov");
        $res .= "<br>";
        //$res .= self::getRoster("mitko_virtual");
        $res .= "<br>";
        //$res .= "Add roster result: " . self::addRoster("dimitar_minekov", array("contact" => "mitko_virtual@jabber.bags.bg"));
//         $res .= "<br>";
        //$res .= "Add roster result: " . self::addRoster("milen", "dimitar_minekov@jabber.bags.bg");
        $res .= "<br>";
        $res = self::getConnectedUsers();
        
        bp ($res);
        
    }
}