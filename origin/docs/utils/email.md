# Email

The email class enables you to send emails easily through SMTP.

You setup your email accounts in your `config/email.php` we have created a template for you, just rename the file and fill your details.

````php
use Origin\Utils\Email;

Email::config(
    'default',[
        'host' => 'smtp.example.com',
        'port' => 25,
        'username' => 'demo@example.com',
        'password' => 'secret',
        'timeout' => 5
        ]
    );
````

The keys for the config are as follows:

- *host* this is smtp server hostname, if you will connect with ssl then you will need to add the protocol prefix `ssl://` to host, so it would be `ssl://smtp.example.com`.
- *port* port number default 25
- *username* the username to access this SMTP server
- *password* the password to access this SMTP server
- *tls* default is false, set to true if you want to enable TLS
- *timeout* how many seconds to timeout
- *client* When we send the HELO command to the sever we have to identify your hostname, so we will use localhost or HTTP_SERVER var if client is not set.

If a config for `default` is found this will be usd by default.

To send a text email it would look like this:

````php
use Origin\Utils\Email;

    $Email = new Email();
    $Email->to('somebody@originphp.com')
        ->from('me@@originphp.com')
        ->subject('This is a test')
        ->textMessage('This is the text content')
    $Email->send();

````

To send a HTML email  (if you are going to send HTML you should send both, see below)

````php
use Origin\Utils\Email;

    $Email = new Email();
    $Email->to('somebody@originphp.com')
        ->from('me@@originphp.com')
        ->subject('This is a test')
        ->htmlMessage('<p>This is the html content</p>')
    $Email->send();

````

To send both HTML and text

````php
use Origin\Utils\Email;

    $Email = new Email();
    $Email->to('somebody@originphp.com')
        ->from('me@@originphp.com')
        ->subject('This is a test')
        ->textMessage('This is the text content')
        ->htmlMessage('<p>This is the html content</p>')
        $Email->send();

````

To change the email account 

````php
use Origin\Utils\Email;

    $Email = new Email();
    $Email->to('somebody@originphp.com')
        ->from('me@@originphp.com')
        ->subject('This is a test')
        ->textMessage('This is the text content')
        ->account('gmail')
    $Email->send();

````

To add attachments

````php
use Origin\Utils\Email;

    $Email = new Email();
    $Email->to('somebody@originphp.com')
        ->from('me@@originphp.com')
        ->subject('This is a test')
        ->textMessage('This is the text content')
        ->addAttachment($filename1)
        ->addAttachment($filename2,'Logo.png')
    $Email->send();

````