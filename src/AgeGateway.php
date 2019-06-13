<?php
namespace EighteenPlus\AgeGateway;

use Firebase\JWT\JWT;
use jucksearm\barcode\QRcode;

class AgeGateway 
{
    public function __construct($baseUrl = '')
    {
        $this->baseUrl = $baseUrl;
        
        $this->title = 'The AgeGateway Page';
        $this->siteLogo = null;
        
        $this->siteName = null;
        $this->customText = null;
        $this->customLocation = 'top';
        
        $this->backgroundColor = null;
        $this->textColor = null;
        
        $this->removeReference = false;
        $this->removeVisiting = false;
        
        $this->testMode = false;
        $this->testAnyIp = false;
        $this->testIp = null;
        
        $this->startFrom = '2019-07-15T12:00';
        $this->desktopSessionLive = 24;
        $this->mobileSessionLive = 24;
    }
    
    public function run()
    {
        if (!$this->canStart()) {
            return;
        }
        
        // postback request
        if (strpos($_SERVER['REQUEST_URI'], 'ageverificationpostback') !== false) {
            echo $this->callbackVerify();
            exit;
        }
        
        $this->sessionInit();
        
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
    
    private function sessionInit()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['startedAt'])) {
            $_SESSION['ageVerified'] = false;
            $_SESSION['startedAt'] = time();
        }
        
        $detect = new \Mobile_Detect();
        if ($detect->isMobile() || $detect->isTablet()) {
            $rate = $this->mobileSessionLive;
        } else {
            $rate = $this->desktopSessionLive;
        }
        
        if ($_SESSION['startedAt'] + 3600 * $rate < time()) {
            unset($_SESSION['startedAt']);
            
            return $this->sessionInit();
        }
    }
    
    private function canStart()
    {
        $CrawlerDetect = new CrawlerDetect;
        $exclusions = new CrawlerExclusions();
        $exclusions->add(array('PostmanRuntime\/[\d\.]*', 'Go-http-client\/[\d\.]*', 'Dalvik', '18Plus'));
        $CrawlerDetect->setExclusions($exclusions);
        
        if ($CrawlerDetect->isCrawler()) {
            return false;
        }
        
        return $this->testMode || strtotime($this->startFrom) <= time();
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
    
    public function setSiteName($siteName)
    {
        if ($siteName) {
            $this->siteName = $siteName;
        }
    }
    
    public function setCustomText($customText)
    {
        if ($customText) {
            $this->customText = $customText;
        }
    }
    
    public function setCustomLocation($customLocation)
    {
        if ($customLocation) {
            $this->customLocation = $customLocation;
        }
    }
    
    public function setBackgroundColor($backgroundColor)
    {
        if ($backgroundColor) {
            $this->backgroundColor = $backgroundColor;
        }
    }
    
    public function setTextColor($textColor)
    {
        if ($textColor) {
            $this->textColor = $textColor;
        }
    }
    
    public function setRemoveReference($removeReference)
    {
        if ($removeReference) {
            $this->removeReference = $removeReference;
        }
    }
    
    public function setRemoveVisiting($removeVisiting)
    {
        if ($removeVisiting) {
            $this->removeVisiting = $removeVisiting;
        }
    }
    
    public function setTestMode($testMode)
    {
        $this->testMode = (bool)$testMode;
    }
    
    public function setTestAnyIp($testAnyIp)
    {
        $this->testAnyIp = (bool)$testAnyIp;
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
    
    public function setDesktopSessionLifetime($desktopSessionLive)
    {
        if ($desktopSessionLive) {
            $this->desktopSessionLive = intval($desktopSessionLive);
        }
    }
    
    public function setMobileSessionLifetime($mobileSessionLive)
    {
        if ($mobileSessionLive) {
            $this->mobileSessionLive = intval($mobileSessionLive);
        }
    }
    
    public function IPCheck() 
    {
        if ($this->testAnyIp || $this->testIp == Utils::getClientIp()) {
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
            return 'done';
        }
        
        return 'c_wait';
    }
    
    public function callbackVerify() 
    {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['jwt'])) {
            return 'error';
        }
        
        try {
            $jwt = $input['jwt'];
            $publicKey = base64_decode(Utils::$JWT_PUB);
            JWT::$leeway = 270;
            $decoded = JWT::decode($jwt, $publicKey, ['RS256']);
            
            session_id($decoded->agid);
            session_start();
            
            $_SESSION['ageVerified'] = true;
            
            return 'complete';
        } catch (\Firebase\JWT\BeforeValidException $e) {
            return 'validation_error';
        }
    }

    public function viewTemplate() 
    {
        $qrCodeUrl = Utils::makeUrl($this->baseUrl);
        $qrCode = QRcode::factory();
        $qrCode->setCode($qrCodeUrl);
        $qrCode->setSize(300);
        $qrCode->setLevel('H');
        $qrCode = $qrCode->getQRcodePngData();
        $qrCode = Utils::insertLogo($qrCode, $this->siteLogo);
        
        $deepurl = Utils::makeUrl($this->baseUrl, true);
        
        return $this->renderTemplate([
            'title'     => $this->title,
            'siteLogo'  => $this->siteLogo,
            'showLogo'  => $this->siteLogo ? 'display: block' : 'display: none;',
            
            'siteName'  => $this->siteName,
            'customText'  => $this->customText,
            'customLocationTopShow'  => $this->customLocation == 'top' ? 'display: block;' : 'display: none;',
            'customLocationBottomShow'  => $this->customLocation == 'bottom' ? 'display: block;' : 'display: none;',
            
            'backgroundColor' => $this->backgroundColor ?: 'rgb(247, 241, 241)',
            'textColor' => $this->textColor ?: '#212529',
            
            'removeReference' => $this->removeReference ? 'none' : 'block',
            'removeVisiting' => $this->removeVisiting ? 'none' : 'block',
            
            'deepurl'   => $deepurl, 
            'qrCode'    => Utils::imgToBase64($qrCode),
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
