<?php
namespace EighteenPlus\AgeGate;

require("GbIpData.php");

use Firebase\JWT\JWT;

class Utils
{
    static $AgeCheckURL1 = "https://applink.18plus.org/agecheck";
    static $AgeCheckURL2 = "org18plus://agecheck";
    
    static $JWT_PUB = 'LS0tLS1CRUdJTiBQVUJMSUMgS0VZLS0tLS0KTUlJQklqQU5CZ2txaGtpRzl3MEJBUUVGQUFPQ0FROEFNSUlCQ2dLQ0FRRUF6YjRtcjhqcHh3NXJSU2pqK1NEQQo2cG9GNlFmaXp4dEtUZlVWQTYwTG1XTXJQeS93MWF4KzBsb1lxWWRYT2lVRmhETWhSQ2JiQjVaTmhzcDFEbklnCm03NTdVMldIaXJhOVFQcUNXTmo4Ymo0L1dxN0FwT3hFT0ZQVWFLeTVZZlRjaWQxU3VLWHpZNDNWa21NYUdUYnUKOXFJTWRzcitHU2lTTmdzZlNEcVNIeG4wL0Z5aFFkZTcwbWZjMTh1V3h5ZGVXTm5hRkhjeUZpMWFsbWUyZGREZQpHSlRta043YkZUT2ZHZXM5RkdDZWZzckI3MDRMcE8wcHo2ZjhHNlhsVmZQb0IwY2liWno3SlpHU0g5bHB1RkVkCm5MM2RVRFdvL3BBNzR3REJsSncrVThZWkN3eG1jeFZLVWRwejV1ZUJOMGc1WnN0czhjQjV6Y2V2aHZHSUIzazMKOVFJREFRQUIKLS0tLS1FTkQgUFVCTElDIEtFWS0tLS0tCg==';
    
    public static function getClientIp() 
    {
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_X_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if(isset($_SERVER['REMOTE_ADDR']))
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = 'UNKNOWN';
        
        return $ipaddress;
    }
    
    public static function currentUrl()
    {
        $url = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $url .= "://$_SERVER[HTTP_HOST]";
        $url .= $_SERVER['REQUEST_URI'];
        
        return $url;
    }
    
    public static function IPToUint32($ip)
    {
        $ss = explode(".", $ip);

        $i0 = intval($ss[0]);
        $i1 = intval($ss[1]);
        $i2 = intval($ss[2]);
        $i3 = intval($ss[3]);

        $result = ($i0 << 24) | ($i1 << 16) | ($i2 << 8) | $i3;

        return $result;
    }
    
    public static function isGB($ip1)
    {
        $ip2 = Utils::IPToUint32($ip1);
        $count = count(ipranges);
        for ($i = 0; $i < $count; $i += 2) {
            if (!array_key_exists($i+1, ipranges)) {
                return false;
            }
            
            if (ipranges[$i] <= $ip2 && $ip2 <= ipranges[$i+1]) {
                return true;
            }
        }
        
        return false;
    }
    
    public static function makeUrl($baseUrl, $deep = false) 
    {
        $AgeCheckURL1 = self::$AgeCheckURL1;
        $AgeCheckURL2 = self::$AgeCheckURL2;
        
        $publicKey = base64_decode(Utils::$JWT_PUB);
        $encoded = JWT::encode(session_id(), $publicKey, 'HS256');
        
        $postback = $baseUrl . '?jwt=' . $encoded;
        
        // detect deepurl for ios devices
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        if ($deep) {
            $type = 2;
            if (strpos($ua, "iPhone") || strpos($ua, "iPad")) {
                $type = 1;
            }
        } else {
            $type = 1;
        }
        
        $deep = $deep ? 'true' : 'false';
        
        $url = ${'AgeCheckURL' . $type} . "?postback=".urlencode($postback)."&deep={$deep}&agent=" .urlencode($ua);
        
        return $url;
    }
    
    public static function insertLogo($qrCode, $siteLogo = null)
    {
        $qr = imagecreatefromstring($qrCode);
        $qr_height = imagesy($qr);
        $qr_width  = imagesx($qr);
        
        $padding = $qr_height / 4 * 0.1;
        $logo_height = $qr_height / 4;
        $logo_width = $qr_width / 4;
        
        $base_height = $logo_height + $padding * 2;
        $base_width = $logo_width + $padding * 2;
        $base = imagecreatetruecolor($base_width, $base_height);
        $white = imagecolorallocate($base, 255, 255, 255);
        imagefill($base, 0, 0, $white);
        
        if ($siteLogo) {
            $logo = self::imageCreateFromAny($siteLogo);
        } else {            
            $logo = imagecreatefrompng(__DIR__ . '/assets/emblem.png');
        }
        $logo = imagescale($logo, $logo_height, $logo_width, IMG_BICUBIC);
        
        imagecopy($base, $logo, $padding, $padding, 0, 0, $logo_height, $logo_width);
        imagecopy($qr, $base, ($qr_height - $base_height) / 2, ($qr_width - $base_width) / 2, 0, 0, $base_height, $base_width);
        
        ob_start();
        imagepng($qr);
        $qrCode = ob_get_contents();
        imagedestroy($qr);
        ob_end_clean();
        
        return $qrCode;
    }
    
    public static function imgToBase64($img)
    {
        if (ctype_print($img) && file_exists( $img )) {            
            $im = imagecreatefrompng($img);
            ob_start(); // Start buffering the output
            imagepng($im, null, 0, PNG_NO_FILTER);
            $b64 = base64_encode(ob_get_contents()); // Get what we've just outputted and base64 it
            imagedestroy($im);
            ob_end_clean();
        } else {
            $b64 = base64_encode($img);
        }
        
        return "data:image/png;base64," . $b64;
    }
    
    private static function imageCreateFromAny($filepath) { 
        $type = exif_imagetype($filepath); // [] if you don't have exif you could use getImageSize() 
        $allowedTypes = array( 
            1,  // [] gif 
            2,  // [] jpg 
            3,  // [] png 
            6   // [] bmp 
        ); 
        if (!in_array($type, $allowedTypes)) { 
            return false; 
        } 
        switch ($type) { 
            case 1 : 
                $im = imageCreateFromGif($filepath); 
            break; 
            case 2 : 
                $im = imageCreateFromJpeg($filepath); 
            break; 
            case 3 : 
                $im = imageCreateFromPng($filepath); 
            break; 
            case 6 : 
                $im = imageCreateFromBmp($filepath); 
            break; 
        }    
        return $im;  
    } 
}