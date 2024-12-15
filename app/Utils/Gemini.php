<?php

namespace App\Utils;

use Gemini\Laravel\Facades\Gemini as FacadesGemini;

class Gemini
{
    public function sentimentalScore($arg): string
    {
        $result = FacadesGemini::geminiPro()->generateContent("return 'ONLY' the sentimental score between 0 to 1 for $arg");
        return $result->text();
    }
}
