<?php
namespace EighteenPlus\AgeGate;

require("GbIpData.php");

use Firebase\JWT\JWT;

class Utils
{
    static $AgeCheckURL = "https://deep.reallyme.net/agecheck";
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
    
    public static function makeUrl($baseUrl) 
    {
        $returnURL = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
        
        $publicKey = base64_decode(Utils::$JWT_PUB);
        $encoded = JWT::encode(session_id(), $publicKey, 'HS256');
        $baseUrl .= '?jwt=' . $encoded;
        $baseUrl .= '&agecheck=true';
        $url = sprintf("%s?postback=%s&url=%s", self::$AgeCheckURL, urlencode($baseUrl), urlencode($returnURL));

        return $url;
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
}