$ports= new-Object System.IO.Ports.SerialPort COM1,1200,Even,7,two
$ports.open()
$ports.close()
