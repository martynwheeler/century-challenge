<?php

namespace App\Message;
use Symfony\Component\HttpFoundation\Request;

class NewRideMessage
{
    public function __construct(private Request $content)
    {
    }

    public function getContent(): Request
    {
        return $this->content;
    }
}