# Number Helper

## format($value, $currency = null ,array $options=[])

Formats a number, this is used by all other functions in the backend.

````php
    echo $this->Number->format(1234.56); // 1,234.56
````

You can also pass and array of options with the following keys:
- *precision* The maximum number of decimal places
- *places* - The minimum number of decimal places
- *before* - What to show before the string
- *after*- What to show after the string
- *pattern* - a [PHP intl](http://php.net/manual/en/class.numberformatter.php) extension pattern e.g #,##0.###

## currency($value, $currency = null ,array $options=[])

Formats a number into a currency. 

````php
    echo $this->Number->currency(1234.56,'USD'); // $1,234.56
````

You can also pass and array of options with the following keys:
- *precision* The maximum number of decimal places
- *places* - The minimum number of decimal places
- *before* - What to show before the string
- *after*- What to show after the string
- *pattern* - a [PHP intl](http://php.net/manual/en/class.numberformatter.php) extension pattern e.g #,##0.###

## precision($value, $precision = 2 ,array $options=[])

Formats a number with a max number of decimal places.,

````php
    echo $this->Number->precision(1234.56,2; // 1,234.56
````

You can also pass and array of options with the following keys:
- *places* - The minimum number of decimal places
- *before* - What to show before the string
- *after*- What to show after the string
- *pattern* - a [PHP intl](http://php.net/manual/en/class.numberformatter.php) extension pattern e.g #,##0.###