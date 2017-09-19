<?php

namespace Newsapp\Models;

class Comments extends \Baka\Database\Model
{
    public $user_id;
    public $news_id;
    public $content;

    public function initialize()
    {
        $this->setSource('news_comments');
        
        $this->belongsTo('user_id', 'Newsapp\Models\Users', 'id', ['alias' => 'users']);
        $this->belongsTo('news_id', 'Newsapp\Models\News', 'id', ['alias' => 'news']);
        
    }
}
