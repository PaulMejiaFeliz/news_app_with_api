<?php

namespace Newsapp\Models;

use Phalcon\Validation;
use Phalcon\Validation\Validator\Email;
use Phalcon\Validation\Validator\StringLength;
use Phalcon\Validation\Validator\Uniqueness;

class Users extends \Baka\Database\Model
{
    public $name;
    public $lastName;
    public $email;
    public $password;

    public function initialize()
    {
        $this->setSource('users');

        $this->hasMany('id', 'Newsapp\Models\News', 'user_id', ['alias' => 'news']);
        $this->hasMany('id', 'Newsapp\Models\Comments', 'user_id', ['alias' => 'comments']);
    }

    public function validation()
    {
        $validator = new Validation();

        $validator->add(
            'email',
            new Email(
                [
                    'message' => 'The e-mail is not valid.',
                ]
            )
        );

        $validator->add(
            "email",
            new Uniqueness()
        );

        $validator->add(
            [
                'name',
                'lastName',
                'email',
                'password'
            ],
            new StringLength(
                [
                    'max' => [
                        'name' => 100,
                        'lastName' => 100,
                        'email' => 30,
                        'password' => 100
                    ],
                    'min' => [
                        'password' => 5
                    ],
                    'messageMaximum' => [
                        'name' => 'The name is too long, 100 characters maximun.',
                        'lastName' => 'The lastname is too long, 100 characters maximun.',
                        'email' => 'The e-mail is too long, 30 characters maximun.',
                        'password' => 'The password is too long, 100 characters maximun.'
                    ],
                    'messageMinimum' => [
                        'password' => 'The password is too short, 5 characters minimun.'
                    ]
                ]
            )
        );

        return $this->validate($validator);
    }
}
