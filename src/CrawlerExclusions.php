<?php

namespace EighteenPlus\AgeGateway;

use Jaybizzle\CrawlerDetect\Fixtures\Exclusions;

class CrawlerExclusions extends Exclusions
{
    public function add($data = array())
    {
        $this->data = array_merge($this->data, $data);
    }
}