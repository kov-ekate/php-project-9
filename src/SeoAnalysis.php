<?php

namespace App;

use DiDom\Document;
use DiDom\Element;

class SeoAnalysis
{
    public function analyze(string $html): array
    {
        $dom = new Document($html);
        $seoData = [];

        $h1 = $dom->first('h1');
        if ($h1) {
            $seoData['h1'] = $h1->text();
        } else {
            $seoData['h1'] = '';
        }

        $title = $dom->first('title');
        if ($title) {
            $seoData['title'] = $title->text();
        } else {
            $seoData['title'] = '';
        }

        $description = $dom->first('meta[name="description"]');
        if ($description) {
            $seoData['description'] = $description->attr('content');
        } else {
            $seoData['description'] = '';
        }

        return $seoData;
    }
}
