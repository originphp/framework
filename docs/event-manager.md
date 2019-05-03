# Event Manager

The Event Manager is a event dispatcher using the Observer pattern. You can use the Event Manager to communicate
to different parts of your application.

The first thing to do is to get the Event Manager

## Getting the Event Manager

```php
use Origin\Event\EventManager;
$manager = EventManager::instance();
```

## Listening for Events

The next thing is to setup the listeners which will be called once an event is dispatched.

You can do this by listening and passing a callable, the event will be passed as the first argument to the function.

```php
 $manager->listen('Order.purchaseComplete',[$this,'sendEmails']);
```

If you want to use an anonymous function then you can do so like this

```php
$manager->listen('Order.purchaseComplete',function (Event $event) {
            $logger->info('A new purchase has been complete');
        });
```

You can also set a priority for the events, this is done by passing a third argument. The default priority is 10.

```php
 $manager->listen('Order.purchaseComplete',[$this,'sendEmails'],5);
```

## Dispatching Events

```php
 $manager->dispatch('Order.purchaseComplete');
```

## Working with Events

If the function returns a result, you can access this using

```php
$result = $event->result();
```

If the function returns false, then the event will be stopped automatically, and no other listeners will be
called.

You can manually stop the event using `$event->stop()`.

If you need to pass data between the listeners, you can set and get data in the event using the `data` method.

```php
$event->data(['name'=>'Joe Bloggs']);

$data = $event->data();
```

Sometimes you will want to pass an object to an event, this can also be done.

```php
 $event = $manager->new('Order.purchaseComplete',$this);
 $manager->dispatch($event);
```

If you also want to set the data for the event when creating the event object

```php
 $event = $manager->new('Order.purchaseComplete',$this,$optionalData);
 $manager->dispatch($event);
```

### Subscribing

If you are using multiple events on an object you can setup a method called `implementedEvents` and return an array of event key and function to call. Then  subscribe the object using the Event Manager

```php
class Something
{
    public function implementedEvents(){
        return [
            'Something.startup' => 'startup',
            'Someting.shutdown' => 'shutdown'
        ];
    }
    public function setup(){
        $manager = EventManager::instance();
        $manager->subscribe($this);
    }
}

```