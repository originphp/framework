# Email

The email class enables you to send emails easily through SMTP.

You setup your email accounts in your `config/email.php` we have created a template for you, just rename the file and fill your details.

```php
use Origin\Utility\Email;

Email::config(
    'default',[
        'host' => 'smtp.example.com',
        'port' => 25,
        'username' => 'demo@example.com',
        'password' => 'secret',
        'timeout' => 5,
        'tls' => false
        ]
    );
```

The keys for the config are as follows:

- *host* this is smtp server hostname, if you will connect with ssl then you will need to add the protocol prefix `ssl://` to host, so it would be `ssl://smtp.example.com`.
- *port* port number default 25
- *username* the username to access this SMTP server
- *password* the password to access this SMTP server
- *tls* default is false, set to true if you want to enable TLS
- *timeout* how many seconds to timeout
- *client* When we send the HELO command to the sever we have to identify your hostname, so we will use localhost or HTTP_SERVER var if client is not set.
- *debug* If set and is true the headers and message is rendered and returned (without sending via SMTP)

You can also pass keys such as `from`,`to`,`cc`,`bcc`,`sender` and `replyTo` this pass the data to its functions either as string if its just an email or an array if you want to include a name. Remember if you are going to automatically cc or bcc somewhere, then you have to next call addBcc or addCc to ensure that you don't overwrite this.

For example

```php
    [
        'from' => 'james@originphp.com'
        'bcc' => ['someone@origin.php', 'Someone']
    ]
```

If a config for `default` is found this will be used unless you specify something else with the `account`.

To send a text email (default) it would look like this:

```php
use Origin\Utility\Email;

    $Email = new Email();
    $Email->to('somebody@originphp.com')
        ->from('me@originphp.com')
        ->subject('This is a test')
        ->textMessage('This is the text content')
    $Email->send();

```

To send a HTML email  (if you are going to send HTML you should send both, see below)

```php
use Origin\Utility\Email;

    $Email = new Email();
    $Email->to('somebody@originphp.com')
        ->from('me@originphp.com')
        ->subject('This is a test')
        ->htmlMessage('<p>This is the html content</p>')
        ->format('html');
    $Email->send();

```

To send both HTML and text

```php
use Origin\Utility\Email;

    $Email = new Email();
    $Email->to('somebody@originphp.com')
        ->from('me@originphp.com')
        ->subject('This is a test')
        ->textMessage('This is the text content')
        ->htmlMessage('<p>This is the html content</p>')
        ->format('both');
        $Email->send();

```

To change the email account (accounts are setup using the Email::config() usually in the config/email.php)

```php
use Origin\Utility\Email;

    $Email = new Email();
    $Email->to('somebody@originphp.com')
        ->from('me@originphp.com')
        ->subject('This is a test')
        ->textMessage('This is the text content')
        ->account('gmail');
    $Email->send();

```

You can also setup the config during the creation of the Email object.

```php
use Origin\Utility\Email;
    $config = [ 
        'host' => 'ssl://smtp.gmail.com',
        'port' => 465,
        'username' => 'email@gmail.com',
        'password' => 'secret'
        ];
    $Email = new Email($config);
    

```


To add attachments

```php
use Origin\Utility\Email;

    $Email = new Email();
    $Email->to('somebody@originphp.com')
        ->from('me@originphp.com')
        ->subject('This is a test')
        ->textMessage('This is the text content')
        ->addAttachment($filename1)
        ->addAttachment($filename2,'Logo.png');
    $Email->send();

```

To use templates

Templates are stored in the `View/Email` folder, use the template method to set the name and use the set method to send variables to the templates.

```php
    use Origin\Utility\Email;
    $Email = new Email();
    $Email->to('somebody@originphp.com')
        ->from('me@originphp.com')
        ->subject('This is a test')
        ->template('welcome')
        ->set(['first_name'=>'Frank'])
        ->format('both')
    $Email->send();
```

Here is how you use variables in the email templates:

```php
// View/Email/html/welcome.ctp
<p>Hi <?= $first_name ?></p>
<p>How is your day so far?</p>
```

The template method also accepts plugin syntax, so to load a template from a plugin  folder just add the plugin name followed by a dot then the template name.

```php
use Origin\Utility\Email;


    $Email = new Email();
    $Email->to('somebody@originphp.com')
        ->from('me@originphp.com')
        ->subject('This is a test')
        ->template('ContactManager.reset_password')
        ->format('both')
    $Email->send();

```