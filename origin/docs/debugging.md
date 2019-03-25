# Debugging 

## Profiling

To use Xdebug for profiling you will need to enable the extension by adding the following code
to the PHP.ini.   These can't be echoed into the container if you are trying to profile web requests since you will need to restart the docker container.

````
zend_extension="/usr/lib/php/20170718/xdebug.so"
xdebug.default_enable=0
````

To enable the profiling add the following

````
xdebug.profiler_enable = 0'
xdebug.profiler_enable_trigger = 1
xdebug.profiler_enable_trigger_value = ""
xdebug.profiler_output_dir = "/var/www/tmp"
xdebug.profiler_output_name = "xdebug-profiler.%p"
````

Each url add the param

http://localhost/contacts/add?XDEBUG_PROFILE=1