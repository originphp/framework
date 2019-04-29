# Dom Utility

The Dom utility extends the dom extension and provides javascript style query selectors (even when xpath fails).

The two functions functions that it adds to the PHP dom extension are `querySelector` and `querySelectorAll`. Unlike most other dom libraries, this does not use xpath to find elements. This is because during development, I found that translating my selectors from existing javascript code to xpath, i was not able to find the elements. When I tried downloading other libraries the same problem occurred. So I had to build from ground up so that the selector works the same as in javascript.

Currently it supports the following selectors:

 - `.class` selects by class name
 - `#id` selects by id
 - `*` selects all elements
 - `element` selects by tag name e.g. h1
 - `element.class` selects a tag name with a class e.g. h1.heading  will return all the h1 elements with the class heading
 - `element,element` - Selects either elements e.g div,p will select all divs and paragraphs
 - `element element` - e.g. div p will select all p in a div
 - `[attribute=value]` - e.g. a[target='_blank'] will select all links with target = _blank
 - `:first-child` - e.g. p:first-child will select the first child element from the p
 - `:last-child` - e.g. p:last-child will select the last child element from the p
 - `:nth-child(x)` = e.g. p:3 will select the third child. 

At the moment it does not support the `~>+^|$` selectors.

Example usage:

```php
use Origin\Utility\Dom;
$dom = new Dom();
$dom->loadHtml($html); // this is dom function
$element = $dom->querySelector('div.foo div.sub h1');
$paragraphs = $dom->querySelectorAll('div.p');
```