<?php
namespace App\Controller;

use App\Controller\AppController;

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
