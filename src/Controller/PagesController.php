<?php

namespace App\Controller;

use Origin\Controller\Controller;

class PagesController extends Controller
{
    public $layout = false;

    public function display()
    {
        $args = func_get_args();

        $count = count($args);
        if (!$count) {
            return $this->redirect('/');
        }

        return $this->render('Pages/'.implode('/', $args));
    }
}
