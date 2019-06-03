<?php

namespace EighteenPlus\AgeGate;

class CrawlerDetect extends \Jaybizzle\CrawlerDetect\CrawlerDetect
{
    public function setExclusions(\Jaybizzle\CrawlerDetect\Fixtures\Exclusions $exclusions)
    {
        $this->exclusions = $exclusions;
        $this->compiledExclusions = $this->compileRegex($this->exclusions->getAll());
    }
}