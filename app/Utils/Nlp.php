<?php

namespace App\Utils;

use PHPInsight\Sentiment;

class Nlp
{
    public function sentimentalScore($arg)
    {
        $sentiment = new Sentiment();
        $score = $sentiment->score($arg);
        return $score['pos'];
    }
}
