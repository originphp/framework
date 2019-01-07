<?php
namespace App\Controller;

/**
 * This is an optional controller for serving static page content. For example
 * /pages/about_us will load the view file View/Pages/about_us.ctp
 *
 *  Router::add('/pages/*', ['controller'=>'Pages','action'=>'display']);
 */
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
