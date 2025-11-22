<?php

namespace App;

use DiDom\Document;
use DiDom\Element;
use Illuminate\Support\Optional;

class SeoAnalysis
{
    public function analyze(string $html): array
    {
        $dom = new Document($html);
        $seoData = [];

        $h1 = (string) optional($dom->first('h1'))->text();
        $seoData['h1'] = $h1;

        $title = (string) optional($dom->first('title'))->text();
        $seoData['title'] = $title;

        $descriptionElement = $dom->first('meta[name="description"]');
        $seoData['description'] = (string) optional($descriptionElement)->attr('content');

        return $seoData;
    }
}
