<?php


/**
 * NFC/RFID карти
 *
 * @category  bgerp
 * @package   bgerp
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bgerp_C extends core_Mvc
{
    
 


    public function act_Img()
    {

        requireRole('admin');
        // Ensure you have the GD library installed in PHP

        $width = 2000;
        $height = 1250;

        // Create a blank image with white background
        $image = imagecreatetruecolor($width, $height);
        
        $code = Request::get('code');

        srand(crc32($code));

        // Switch antialiasing on for one image
        imageantialias($image, true);
        $redB = rand(80, 170);
        $greenB = rand(80, 170);
        $blueB = rand(80, 170);
        $white = imagecolorallocate($image, $redB, $greenB, $blueB);
        imagefill($image, 0, 0, $white);
;
        // Generate random circles
        for ($i = 0; $i < 40; $i++) {
            $j = $i * 2;
            $x = rand(0, $width);
            $y = rand(0, $height);
            $radius = rand(10, ($height+$width)/4 - $j);
            $red = $redB + rand(-$j, $j);
            $green = $greenB + rand(-$j, $j);
            $blue = $blueB + rand(-$j, $j);
            $alpha = rand(20, 110 - $j); // Semi-transparent value
            $color = imagecolorallocatealpha($image, $red, $green, $blue, $alpha);
            
            imagefilledellipse($image, $x, $y, $radius, $radius, $color);
            $x = rand(0, $width);
            $y = rand(0, $height);
            $radius = rand(10, ($height+$width)/4 - $j);
            $red = $redB + rand(-$j, $j);
            $green = $greenB + rand(-$i, $i);
            $blue = $blueB + rand(-$j, $j);
            $alpha = rand(20, 110 - $j); // Semi-transparent value
            $color = imagecolorallocatealpha($image, $red, $green, $blue, $alpha);
            
            imagefilledellipse($image, $x, $y, $radius, $radius, $color);
        }
 

        // Output the image
        header("Content-Type: image/png");
        imagepng($image);
        imagedestroy($image);
 
    }


    public function act_Cards()
    {
        requireRole('admin');

        $tpl = new ET('
        <!DOCTYPE html>
            <html lang="en">
              
            <head>
                <meta charset="UTF-8" />
                <meta http-equiv="X-UA-Compatible" 
                      content="IE=edge" />
                <meta name="viewport" 
                      content="width=device-width, initial-scale=1.0" />
                <link rel="preconnect" href="https://fonts.googleapis.com">
                <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
                <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@200;300&display=swap" rel="stylesheet"> 
                <title>
                    Access Cards for bgERP
                </title>
                <style>
                    .container {
                        position: relative;
                        width: 85.5mm;
                        height: 54mm;
                        display: table-cell;
                        text-align: center;
                        vertical-align: middle;
                        border: 0;
                        margin-bottom:10px;
                        display:block;
                        background-position: center; 
                        background-size:cover;
                        border-radius:10px;
                        page-break-after: always;
                    }
                    .qr {
                        border:solid 1px black;
                        position: absolute;
                        top: 50%; left: 50%;
                        transform: translate(-50%,-50%);
                        width:220px;
                        height:160px;
                        background-color:rgba(255,255,255, 0.8);
                        border-radius:15px;
                        writing-mode: vertical-lr;
                        text-orientation: mixed;
                        font-size:0.95em;
                        font-family: \'Oswald\', sans-serif;
                    }

                    .qr img {
                        position: absolute;
                        top: 50%; left: 50%;
                        transform: translate(-50%,-50%);
                    }

                    .info {
                        position:absolute;
                        right:5px;
                        top:20px;
                        font-size:0.85em;

                    }
 
                    @page  {
                      size: 85.5mm 54mm;
                      margin:0;
                      padding:0;
                    }

                    @media print {
                    body {
                        margin: 0;
                    }
            }


                </style>
            </head>
              
            <body>
                [#content#]
            </body>
              
            </html>');

    
        for($i = 0; $i < 10; $i++) {
            $code = base_convert(random_int(1000000000, 4000000000), 10, 36);
            $qrUrl = barcode_Qr::getUrl("https://bcvt.eu/C/{$code}", false, 5);

            $card = "<div class='container' style=' background-image: url(\"/C/Img/?code={$code}\")'><div class='qr'><p>www.bcvt.eu/C/{$code}</p><img src='{$qrUrl}'><p class='info'>Регистрирайте се на адрес:</p></div></div>";
            

            $tpl->append($card, 'content');


        }

        echo $tpl->getContent();

        die;
    }

}
