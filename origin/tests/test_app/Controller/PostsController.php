<?php
namespace TestApp\Controller;

use TestApp\Controller\AppController;

class PostsController extends AppController
{
    public function index()
    {
        $this->set('title', 'Posts Controller Page');
    }
    public function list()
    {
        $this->layout = false;
        $this->set('data', ['error'=>'Noting to list']);
    }
}
