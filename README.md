Oyster Journey
==============

This class is a simple scraper class that allows for users of the TFL Oyster website to scrape their journey information and access it as a nice array.

I have a live example [here](http://ollieread.com/labs/oyster-journey/).

In total the class took me around an hour, so I apologise if it seems overly hacky but scraping is not as easy as one would like it to be.

Bug fixes, use cases, updates, suggestions and examples of this being used are welcomed, no, **encouraged**!

## Quick Example ##

```php
<?php
require_once 'OysterJourney.php';

try {
    // grab it
    $OysterJourney = new OysterJourney();
    // check its status
    if($OysterJourney->didItWork() === true) {
        // get the journey info
        $data = $OysterJourney->getJourney();
        // output
        echo '<pre>';
        print_r($data);
        echo '</pre>';
    }
} catch(Exception $e) {
    // you fucking idiot, you broke my code!
    echo $e->getMessage();
}
```
