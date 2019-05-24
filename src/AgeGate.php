<?php
namespace EighteenPlus\AgeGate;

use Firebase\JWT\JWT;
use jucksearm\barcode\QRcode;

class AgeGate 
{
    public function __construct($baseUrl = '')
    {
        $this->title = 'The AgeGate Page';
        $this->baseUrl = $baseUrl;
        $this->siteLogo = null;
        $this->testIp = null;
        $this->startFrom = '2019-07-15T12:00';
    }
    
    public function run()
    {
        if (!$this->canStart()) {
            return;
        }
        
        // postback request
        if (isset($_REQUEST['agecheck'])) {
            echo $this->callbackVerify();
            exit;
        }
        
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // ajax verify check from template
        if (isset($_GET['ajaxVerify'])) {
            echo $this->ajaxVerify();
            exit;
        }
        
        if (!$this->isVerified() && $this->IPCheck()) {
            echo $this->viewTemplate();
            exit;
        }
    }
    
    private function canStart()
    {
        return strtotime($this->startFrom) <= time();
    }
    
    public function setTitle($title)
    {
        if ($title) {            
            $this->title = $title;
        }
    }
    
    public function setLogo($logo)
    {
        if ($logo) {            
            $this->siteLogo = $logo;
        }
    }
    
    public function setTestIp($testIp)
    {
        if ($testIp) {
            $this->testIp = $testIp;
        }
    }
    
    public function setStartFrom($startFrom)
    {
        if ($startFrom) {
            $this->startFrom = $startFrom;
        }
    }
    
    public function IPCheck() 
    {
        if ($this->testIp == Utils::getClientIp()) {
            return true;
        }
        
        return Utils::isGB(Utils::getClientIp());
    }
    
    public function isVerified() 
    {
        if (isset($_SESSION['ageVerified']) && $_SESSION['ageVerified']) {
            return true;
        }
        
        return false;
    }
    
    public function ajaxVerify()
    {
        // ajax request from template
        if (isset($_SESSION['ageVerified']) && $_SESSION['ageVerified']) {
            session_regenerate_id(true);
        
            return 'done';
        }
        
        return 'c_wait';
    }
    
    public function callbackVerify() 
    {
        try {
            if (!isset($_REQUEST['jwt'])) {
                return 'error';
            }
            
            $jwt = $_REQUEST['jwt'];
            $publicKey = base64_decode(Utils::$JWT_PUB);
            $decoded = JWT::decode($jwt, $publicKey, ['HS256']);
            
            session_id($decoded);
            session_start();
            
            $_SESSION['ageVerified'] = true;
            
            return 'complete';
        } catch (\Exception $e) {
            return 'error';
        }
    }

    public function viewTemplate() 
    {
        if (!isset($_SESSION['ageVerified'])) {
            $_SESSION['ageVerified'] = false;
        }
        
        $deepurl = Utils::makeUrl($this->baseUrl);
        $qrcode = QRcode::factory();
        $qrcode->setCode($deepurl);
        $qrcode->setSize(300);
        $qrCode = $qrcode->getQRcodePngData();
        
        return $this->renderTemplate([
            'plus18Img' => Utils::imgToBase64(__DIR__.'/assets/logo.png'),
            'deepurl'   => $deepurl, 
            'title'     => $this->title,
            'qrCode'    => Utils::imgToBase64($qrCode),
            'siteLogo'  => $this->siteLogo,
            'showLogo'  => $this->siteLogo ? 'display: block' : 'display: none;',
        ]);
    }
    
    protected function renderTemplate($data = array())
    {
        $templateFile = __DIR__.'/assets/template.html';            
        $templatefileResource = fopen($templateFile, 'r') or die('Unable to open file!');
        $templateContent = fread($templatefileResource, filesize($templateFile));
        fclose($templatefileResource);
        
        foreach ($data as $key => $string) {            
            $templateContent = str_replace("%{$key}%", $string, $templateContent);
        }
        
        return $templateContent;
    }
};
