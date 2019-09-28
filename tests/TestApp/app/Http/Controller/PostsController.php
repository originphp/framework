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
}
