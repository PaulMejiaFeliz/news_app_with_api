<?php

namespace Newsapp;

class ValidationException extends \Exception
{
    public $errors;

    public function __construct(array $errors)
    {
        $this->errors = $errors;
    }

    public function getErrors()
    {
        return $errors;
    }
}
