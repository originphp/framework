<?php
namespace App\Http\Controller;

class PostsController extends ApplicationController
{
    public function index()
    {
        $this->set('title', 'Posts Controller Page');
    }
    public function list()
    {
        $this->layout = false;
        $this->set('data', ['error' => 'Noting to list']);
    }

    // Nothing to do with controller, just to aid testing
    public function setProperty($key, $value)
    {
        $this->$key = $value;
    }
    public function getProperty($key)
    {
        return $this->$key ?? null;
    }
}
