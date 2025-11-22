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
        $descriptionContent = '';

        if ($descriptionElement) {
            if ($descriptionElement instanceof \DiDom\Element) {
                $descriptionContent = optional($descriptionElement)->attr('content');
            } elseif ($descriptionElement instanceof \DOMElement) {
                $descriptionContent = optional($descriptionElement)->getAttribute('content');
            }
        }

        $seoData['description'] = (string) $descriptionContent;

        return $seoData;
    }
}
