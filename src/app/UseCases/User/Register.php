<?php

include(__DIR__ . '../../Models/User.php');

class Register
{

    public function __construct()
    {
        $this->user = new User();
    }

    public function registerUser($event)
    {
        $this->user->registerUser($event->getUserId());
    }
}