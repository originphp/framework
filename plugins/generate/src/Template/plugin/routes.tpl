<?php 
use Origin\Http\Router;

Router::add('/%underscored%/:controller/:action/*', ['plugin'=>'%plugin%']);