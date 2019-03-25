# Intl

The IntlHelper uses PHP's intl extension to format dates and numbers. The date and number utilitites that it uses can also parse international dates and numbers (but thats a minefield if you ask me - and if you want to use a datepicker...).

## Configuriation

To use the INTL Helper you need to configure the I18n, you can set this to autodetect based upon the users browser or manually set the configuration.

```php
class AppController extends Controller
{
    public function initialize(){
        I18n::initialize();
    }
}
```

To manually set

```php
class AppController extends Controller
{
    public function initialize(){
        I18n::initialize(['locale' => 'en_GB','language'=>'en','timezone'=>'Europe/London']);
    }
}
```

## Number

Formats a number, this is used by all other functions in the backend.

```php
    echo $this->Intl->number(1234.56); // 1,234.56
```

You can also pass and array of options with the following keys:

- *precision* The maximum number of decimal places
- *places* - The minimum number of decimal places
- *before* - What to show before the string
- *after*- What to show after the string
- *pattern* - a [PHP intl](http://php.net/manual/en/class.numberformatter.php) extension pattern e.g #,##0.###

## Currency

Formats a number into a currency.

```php
    echo $this->Intl->currency(1234.56,'USD'); // $1,234.56
```

You can also pass and array of options with the following keys:
- *precision* The maximum number of decimal places
- *places* - The minimum number of decimal places
- *before* - What to show before the string
- *after*- What to show after the string
- *pattern* - a [PHP intl](http://php.net/manual/en/class.numberformatter.php) extension pattern e.g #,##0.###

## Decimal

Formats a number with a max number of decimal places.,

```php
    echo $this->Intl->decimal(1234.56,2; // 1,234.56
```

You can also pass and array of options with the following keys:
- *places* - The minimum number of decimal places
- *before* - What to show before the string
- *after*- What to show after the string
- *pattern* - a [PHP intl](http://php.net/manual/en/class.numberformatter.php) extension pattern e.g #,##0.###


## Date

```php
echo $this->Intl->date('2018-12-31'); // 31/12/2018
```

## Datetime

```php
echo $this->Intl->datetime('2018-12-31 19:21:00'); // 31/12/2018 19:21
```

## Time

```php
echo $this->Intl->time('19:21:00'); // 19:21
```

You can also pass different formats to the date functions either as string for a pattern or an array.

```php
echo $this->Intl->datetime('2018-12-31 19:21:00','dd MMM, y H:mm'); // Pattern
echo $this->Intl->datetime('2018-12-31 19:21:00',['IntlDateFormatter::NONE, IntlDateFormatter::FULL]); // Array with format options for date + time
```