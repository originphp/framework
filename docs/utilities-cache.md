# Cache

OriginPHP supports, Apcu, Memcache and Redis outof the box for caching, you can also use File caching for
large objects or time consuming generating content.

In your bootstrap file you will the configuration for cache. The caching library can work with multiple configurations and engines at the same time. 

Once the configuration is out the way, using the cache is pretty straightforward.

## Engines

In all these examples,we are only configuring the default configuration, you can set different configuration names instead of default. When you use the caching functions the default configuration is used by default unless you say otherwise.

### File

```php
Cache::config('default', [
    'engine' => 'File',
    'duration' => 3600,
    'prefix' => 'cache_'
     ]);
```

### Apcu

```php
Cache::config('default', [
    'engine' => 'Apcu',
    'duration' => 3600,
    'prefix' => 'cache_'
     ]);
```

### Memcached

This is a simple configuration for using Memcached.

```php
Cache::config('default', [
        'engine' => 'Memcached',
        'host' => '127.0.0.1',
        'port' => '11211',
        'duration' => 3600, 
        'prefix' => 'cache_'
     ]);
```

If your Memcached server is configured with username and password then

```php
Cache::config('default', [
        'engine' => 'Memcached',
        'host' => '127.0.0.1',
        'port' => '11211',
        'username' => 'james',
        'password' => 'secret',
        'duration' => 3600,
        'prefix' => 'cache_'
     ]);
```

If you are going to use socket then instead of setting host and port, then set the `path` key with the location
of the socket.

```php
Cache::config('default', [
         'engine' => 'Memcached',
        'path' => '/var/sockets/memcached',
     ]);
```

You can also make connections persistent by setting the `persistent` key to true, or a string which will be the persistent id.

Memcached supports server pools, if you are going to use them then set an array using the `servers` key instead of host and port. The array should be compatabile with [memcached addservers](http://php.net/manual/en/memcached.addservers.php).

### Redis

This is a simple configuration for using Redis.

```php
Cache::config('default', [
        'engine' => 'Redis',
        'host' => '127.0.0.1',
        'port' => 6379,
        'duration' => 3600,
        'timeout' => 0,
        'prefix' => 'cache_'
     ]);
```

If your Redis server is configured with a password then

```php
Cache::config('default', [
        'engine' => 'Redis',
        'host' => '127.0.0.1',
        'port' =>  6379,
        'password' => 'secret',
        'duration' => 3600,
        'prefix' => 'cache_'
     ]);
```

If you are going to use socket then instead of setting host and port, then set the `path` key with the location
of the socket.

```php
Cache::config('default', [
        'engine' => 'Redis',
        'path' => '/var/sockets/redis',
     ]);
```

You can also make connections persistent by setting the `persistent` key to true, or a string which will be the persistent id.

```php
Cache::config('default', [
        'engine' => 'Redis',
        'host' => '127.0.0.1',
        'port' => 6379,
        'persistent' => 'my-app',
        'duration' => 3600,
        'timeout' => 0,
        'prefix' => 'cache_'
     ]);
```

### Custom Engine

If you want to work with a different backend, it easy to create your own. When configuring cache, instead of passing the  `engine` key, use `className` and include the full namespace. 

```php
Cache::config('default', [
    'className' => 'App\Cache\CustomEngine',
    'duration' => 3600,
    'prefix' => 'cache_'
     ]);
```

```php
namespace App\Cache;
use Origin\Cache\CacheEngine;
class CustomEngine extends CacheEngine
{

}
```

## Caching

### Write

To add an item to the cache.

```php
Use Origin\Cache\Cache;

$success = Cache::write('key',$value);
```
To use a different configuration

```php
Cache::set('key',$value,'long-duration');
```

### Read

To read an item from the cache, if it does not find an item it will return `false`

```php
Use Origin\Cache\Cache;

$value = Cache::read('key');
```
To use a different configuration

```php
$value = Cache::read('key','long-duration');
```

### Check

In most cases using read is good enough, however if you are going to store a boolean result in the cache, then
check will tell you if the key exists. This is handy if the result is false.

```php
Use Origin\Cache\Cache;

if(Cache::check('key')){
    $bool = Cache::read('key);
}
```

To use a different configuration

```php
$result = Cache::check('key','long-duration');
```

### Delete

Items are automatically deleted based upon the duration setting in the configuration, however if you want
to delete an item manually then use the delete method.

```php
Use Origin\Cache\Cache;

Cache::delete('key');
```
To use a different configuration

```php
Cache::delete('key','short-duration');
```

### Clearing the Cache

```php
Cache::clear();
Cache::clear('long-duration');
```

### Enabling and disabling the cache

Sometimes you will need to disable the cache, when you disable we switch the engine to the `NullEngine` and your program can
work as normal.

```php
Cache::disable();
Cache::enable();
```

## Installing Cache Engines

The command line instructions have been tested with Ubuntu 18.x.

### Apcu

Apcu is already install in the docker container, to manually install.

```php
sudo apt-get update
sudo apt-get install php-apcu
sudo echo 'apc.enable_cli=1' >>  /etc/php/7.2/cli/php.ini
```

### Memcached

**To install Memcached in the Docker container**

First add the following to the `docker-compose.yml` file, this will load the memcached image.

```
  memcached:
      image: memcached
```

In the `Dockerfile` add `php-memcached` to the lines where it installs the php extensions
```
    php-memcached \
```
Then run the build command in docker-compose.

```linux
docker-compose build
```

Then set the host to `memcached` in your cache config.

**To install Memcached on a Uubuntu/Debain based server**

```php
sudo apt-get update
sudo apt-get install memcached
sudo apt-get install php-memcached
```

### Redis


**To install Redis in the Docker container**

First add the following to the `docker-compose.yml` file, this will load the Redis image.

```
  redis:
      image: redis
```

In the `Dockerfile` add the following lines to install and enable the Redis PHP extension.
```
RUN pecl install redis
RUN echo 'extension=redis.so' >> /etc/php/7.2/apache2/php.ini
RUN echo 'extension=redis.so' >> /etc/php/7.2/cli/php.ini
```
Then run the build command in docker-compose.

```linux
docker-compose build
```

Then set the host to `redis` in your cache config.

**To install Redis on a Uubuntu/Debain based server**

```php
pecl install redis
sudo echo 'extension=redis.so' >> /etc/php/7.2/apache2/php.ini
sudo echo 'extension=redis.so' >> /etc/php/7.2/cli/php.ini
```