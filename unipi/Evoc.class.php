<?php
/**
 * This class is dedicated to transfer information from and to NeuronS series.
 *
 * @see https://evok.api-docs.io/1.0/rest
 */
class unipi_Evoc
{
    /**
     * JSON data container.
     *
     * @var object
     */
    private $json_data = '';
    
    
    /**
     * IP address of the device.
     *
     * @var string
     */    
    private $ip = '127.0.0.1';
    
    /**
     * Port of the device.
     *
     * @var integer
     */ 
    private $port = 80;


    /**
     * Class constructor.
     *
     * @param string $ip IP address of the device.
     * @param integer $port Port of the device.
     */
    public function __construct($ip, $port)
    {
        $this->setIp($ip);
        $this->setPort($port);
    }


    /**
     * Returns JSON response of the device.
     *
     * @return object Response of the device.
     */
    public function getJsonData()
    {
        return $this->json_data;
    }


    /**
     * Returns IP address of the device.
     *
     * @return string IP address of the device.
     */
    public function getIp()
    {
        return($this->ip);
    }


    /**
     * Set IP address of the device.
     *
     * @param string $ip IP address of the device.
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    }


    /**
     * Returns port of the device.
     *
     * @return string Port of the device.
     */
    public function getPort()
    {
        return($this->port);
    }


    /**
     * Set port of the device.
     *
     * @param integer $port Port of the device.
     */
    public function setPort($port)
    {
        if($port) {
            $this->port = $port;
        }
    }

    /**
     * Generate key data for $json_string.
     * This method is directly related to the EVOK REST API.
     *
     * @param string $uart UART interface.
     * @param integer $dev_id Modbus ID address of the device.
     * @param integer $register Modbus Registers address of the device.
     * @return string Circuit
     * @see https://evok.api-docs.io/1.0/json/get-uart-state-json
     */
    private function generateUartCircuit($uart, $dev_id, $register)
    {
        $value = 0;
        if($register < 10)
        {
            $value = '0'.$register;
        }
        else
        {
            $value = $register;
        }

        $res = 'UART_' . $uart. '_' . $dev_id . '_' . $value;
       
        return $res;
    }
    

    /**
     * Връща масив адрес=>стойност за посочената модбъс шина и устройство
     */
    public function getUartData($uart, $devId)
    {
        $circuit = 'UART_' . ($devId - 1) . '_' . $devId . '_';

        $res = array();

        foreach ($this->json_data as $field)
        {
            if(strpos($field['circuit'], $circuit) === 0) {
                
                $res[(int) str_replace($circuit, '', $field['circuit'])] = $field['value'];
            }
        }
        
        ksort($res);

        return $res;
    }


    /**
     * Търси за записи в evocJson, които отговарят на посочените критерии и после им връща стойността
     */
    public function searchValues($circuit, $dev)
    {
        $res = array();

        foreach ($this->json_data as $field)
        { 
            if(strpos($field['circuit'], $circuit) !== 0) continue;
            if($dev && $dev != $field['dev']) continue;
           
            $key = (int) str_replace($circuit, '', $field['circuit']);
            $res[$key] = $field['value'];
        }
        
        ksort($res);

        return $res;
    }

    
    /**
     * Get device parameter.
     *
     * @param string $parameter Parameter name.
     * @return null, mixed
     */
    private function getDeviceParameter($parameter)
    {
        $value = null;
        foreach ($this->json_data as $field)
        {
            if(isset($field['circuit']) &&
                isset($field['dev'])&&
                isset($field[$parameter]))
            {
                if(($field['circuit'] == '1') &&
                    ($field['dev'] == 'neuron'))
                {
                    $value = $field[$parameter];
                    break;
                }
            }
        }
        return $value;
    }


    /**
     * Make request to the device to update the data.
     * This method is directly related to the EVOK REST API.
     *
     * @see https://evok.api-docs.io/1.0/rest
     */
    public function update()
    {  
        try {
            $response =  fileman::getContent('Y1pGw5');
        } catch (core_exception_Expect $e) {
        }
        
        if(!strlen($response)) {

            // Init CURL object.
            $ch = curl_init();
            curl_setopt_array($ch, array(
                CURLOPT_URL => 'http://'.$this->ip.'/rest/all',
                CURLOPT_PORT => $this->port,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
            ));
            // Get response.
            $response = curl_exec($ch); 
            // Get error.
            $err = curl_error($ch);
            // Clear CURL object.
            curl_close($ch);
            if ($err)
            {
                throw new \Exception($err);
            }
        }

        // Convert to JSON.
        $this->json_data = json_decode($response, true);
    }


    /**
     * Get last connection time. [seconds]
     *
     * @return null, integer
     */
    public function getLastComTime()
    {
        return $this->getDeviceParameter('last_comm');
    }
    
    /**
     * Get device model.
     *
     * @return null, string
     */
    public function getDeviceModel()
    {
        return $this->getDeviceParameter('model');
    }


    /**
     * Get device serial number.
     *
     * @return null, integer
     */
    public function getDeviceSerialNumber()
    {
        return $this->getDeviceParameter('sn');
    }


    /**
     * Get device version.
     *
     * @return null, string
     */
    public function getDeviceVersion()
    {
        return $this->getDeviceParameter('ver2');
    }


    /**
     * Get register data of the device.
     *
     * @param integer $dev_id Modbus ID address of the device.
     * @param integer $register Register address of the device.
     * @return string Register value.
     */
    public function getUartRegister($uart, $dev_id, $register)
    {
        /** @var integer UART register value. $value */
        $value = 0;
        foreach ($this->json_data as $field)
        {
            if(isset($field['circuit']))
            {  
                if($field['circuit'] == $this->generateUartCircuit($uart, $dev_id, $register))
                {
                    if(isset($field['value']))
                    {
                        $value = $field['value'];
                        break;
                    }
                }
            }
        }
        return $value;
    }


    /**
     * Get registers data of the UART device.
     *
     * @param string $uart UART interface.
     * @param integer $dev_id Modbus ID address of the device.
     * @param integer $registers Modbus Registers address of the device.
     * @return array Registers values.
     */
    public function getUartRegisters($uart, $dev_id, $registers)
    {
        /** @var array Registers values. $values */
        $values = array();
        if (empty($registers))
        {
            throw new InvalidArgumentException("Invalid registers.");
        }

        foreach ($registers as $register)
        {
            $values[$register] = $this->getUartRegister($uart, $dev_id, $register);
        }
        return $values;
    }


    /**
     * Get register data of the UART device.
     *
     * @param string $uart UART interface.
     * @param integer $dev_id Modbus ID address of the device.
     * @param integer $register Modbus register address of the device.
     * @param integer $value Modbus register value to be set.
     * @return mixed JSON response data.
     */
    public function setUartRegister($uart, $dev_id, $register, $value)
    {
        // Generate circuit name.
        $circuit = $this->generateUartCircuit($uart, $dev_id, $register);
        
        // Init CURL object.
        $ch = curl_init();
        curl_setopt_array($ch, array(
          CURLOPT_URL => "http://".$this->ip."/rest/register/".$circuit,
          CURLOPT_PORT => $this->port,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => "value=".$value,
        ));
        // Get response.
        $response = curl_exec($ch);
        // Get error.
        $err = curl_error($ch);
        // Clear CURL object.
        curl_close($ch);
        if ($err)
        {
            throw new \Exception($err);
        }
        
        // Convert to JSON.
        return json_decode($response, true); 
    }


    /**
     * Turn the LED state.
     * This method is directly related to the EVOK REST API.
     *
     * @param integer $index [1-4].
     * @param integer $state [0-1].
     * @return string Returns the state of the LED.
     * @see https://evok.api-docs.io/1.0/rest/change-uled-state
     */
    public function turnLed($index, $state)
    {
        // Init CURL object.
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => "http://".$this->ip.'/rest/led/1_0'.$index,
            CURLOPT_PORT => $this->port,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_POST => 1,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "value=".$state,
        ));
        // Get response.
        $response = curl_exec($ch);
        // Get error.
        $err = curl_error($ch);
        // Clear CURL object.
        curl_close($ch);
        if ($err)
        {
            throw new \Exception($err);
        }
        // Convert to JSON.
        return json_decode($response, true);
    }

        
    /**
     * Turn the Relay state.
     * This method is directly related to the EVOK REST API.
     *
     * @param integer $index [1-4].
     * @param integer $state [0-1].
     * @return string Returns the state of the Relay.
     * @see https://evok.api-docs.io/1.0/rest/change-relay-state
     */
    public function turnRelay($index, $state)
    {
        // Init CURL object.
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => "http://".$this->ip.'/rest/relay/1_0'.$index,
            CURLOPT_PORT => $this->port,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_POST => 1,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "value=".$state,
        ));
        // Get response.
        $response = curl_exec($ch);
        // Get error.
        $err = curl_error($ch);
        // Clear CURL object.
        curl_close($ch);
        if ($err)
        {
            throw new \Exception($err);
        }
        // Convert to JSON.
        return json_decode($response, true);
    }


    /**
     * Turn the analog output state.
     * This method is directly related to the EVOK REST API.
     *
     * @param integer $index [1].
     * @param integer $state [0-1].
     * @return string Returns the state of the Relay.
     * @see https://evok.api-docs.io/1.0/rest/change-output-state-relay-alias
     */
    public function turnOutput($index, $state)
    {
        // Create CURL resource.
        $ch = curl_init();
        // Set URL.
        curl_setopt($ch, CURLOPT_URL, 'http://' . $this->ip . ':' . $this->port . '/rest/output/1_0' . $index);
        // Return the transfer as a string.
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // Type POST.
        curl_setopt($ch, CURLOPT_POST, 1);
        // Fields
        curl_setopt($ch, CURLOPT_POSTFIELDS, "value=" . $state);
        // $content Contains the output string.
        $content = curl_exec($ch);
        // Close curl resource to free up system resources.
        curl_close($ch);
        // Returns the state of the LED.
        return $content;
    }
}
