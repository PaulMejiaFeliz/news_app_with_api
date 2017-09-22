<?php

namespace Newsapp\Models;

class Photos extends \Baka\Database\Model
{
    public $url;
    public $news_id;

    public function initialize()
    {
        $this->setSource('photos');
        
        $this->belongsTo('news_id', 'Newsapp\Models\News', 'id', ['alias' => 'news']);
    }
}
