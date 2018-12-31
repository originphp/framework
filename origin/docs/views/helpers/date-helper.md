# Date Helper

The Date helper helps you work with dates from your Views. It uses the Date utility, which has much more features.

## format(string $dateString,$format=null);

The format method will format a date, datetime or time string to the currently set locale by the `I18n` class.

````php
echo $this->Date->format('2018-12-31'); // 31/12/2018
echo $this->Date->format('2018-12-31 19:21:00'); // 31/12/2018 19:21
echo $this->Date->format('19:21:00'); // 19:21
````

You can also pass an array of formats or a pattern that are compatible with the PHP intl extension.

````php
echo $this->Date->format('2018-12-31 19:21:00','dd MMM, y H:mm'); // Pattern
echo $this->Date->format('2018-12-31 19:21:00',['IntlDateFormatter::NONE, IntlDateFormatter::FULL]); // Array with format options for date + time
````