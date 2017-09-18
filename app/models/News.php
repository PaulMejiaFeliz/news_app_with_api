<?php

namespace Newsapp\Models;

use Phalcon\Validation;
use Phalcon\Validation\Validator\StringLength;

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

    public function validation()
    {
        $validator = new Validation();

        $validator->add(
            [
                'title'
            ],
            new StringLength(
                [
                    'min' =>  5,
                    'messageMinimum' =>  'The title is too short, 5 characters minimun.'
                ]
            )
        );

        return $this->validate($validator);
    }
}
