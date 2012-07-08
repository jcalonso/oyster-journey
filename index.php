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