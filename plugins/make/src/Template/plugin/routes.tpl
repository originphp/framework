<?php 
use Origin\Core\Router;

Router::add('/%underscored%/:controller/:action/*', ['plugin'=>'%plugin%']);