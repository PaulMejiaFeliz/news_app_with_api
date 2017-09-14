<?php

namespace Newsapp\Models;

class News extends \Baka\Database\Model
{
    public $title;
    public $content;
    public $user_id;
    public $views = 0;

    public function initialize()
    {
        $this->setSource('news');
        
        $this->belongsTo('user_id', 'Newsapp\Models\Users', 'id', ['alias' => 'users']);
        $this->hasMany('id', 'Newsapp\Models\Comments', 'news_id', ['alias' => 'comments']);
        $this->hasMany('id', 'Newsapp\Models\Photos', 'news_id', ['alias' => 'photos']);
    }
}
