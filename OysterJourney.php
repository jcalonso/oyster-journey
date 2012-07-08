<?php
/**
 * OysterJourney
 * 
 * This class is a simple little hack job that will login into your tfl account
 * and scrape your oyster journey data so that you can provide valid information
 * to your stalkers and people who'd like to mug you/rob your house.
 * 
 * Unfortunately this data isn't realtime and it appears to be updated around
 * once every 12 hours so there's no chance of a realtime foursquare automation,
 * trust me, I tried it.
 * 
 * @author Ollie Read <me@ollieread.com>
 * @version 1.1
 */

class OysterJourney {
    private $_username = ''; // your oyster tfl username
    private $_password = ''; // your oyster tfl password
    private $_cookie_file = ''; // absolute path to a text file to be used to save the cookie in
    private $_success = false;
    private $_order_journey = 'desc';
    private $_order_all = 'desc';
    /**
     * Do some lovely validation so that we can check to make sure everythig is 
     * provided, then attmept the login and grab the cookie
     * 
     * @throws Exception 
     */
    public function __construct() {
        $username = $this->_username;
        $password = $this->_password;
        if(!empty($username) && !empty($password)) {
            $cookie_file = $this->_cookie_file;
            if(!empty($cookie_file) && is_file($cookie_file)) {
                if(is_writable($cookie_file)) {
                    $this->loginAndGetCookie($username, $password, $cookie_file);
                } else {
                    throw new Exception('SHIT AINT WRITABLE');
                }
            } else {
                throw new Exception('BUT WHAT ABOUT THE COOKIE?');
            }
        } else {
            throw new Exception('HOW DO YOU THINK WE WILL LOGIN AS YOU IDIOT?');
        }
    }
    /**
     * Take the information provided and grab the cookie so that we can scrape
     * the journey data
     * 
     * @param string $username
     * @param string $password
     * @param string $cookie_file
     * @throws Exception 
     */
    private function loginAndGetCookie($username, $password, $cookie_file) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_URL, 'https://oyster.tfl.gov.uk/oyster/security_check');
        curl_setopt($curl, CURLOPT_REFERER, 'https://oyster.tfl.gov.uk/oyster/entry.do');
        curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.0; en-US; rv:1.4) Gecko/20030624 Netscape/7.1 (ax)");
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(array(
            'j_username' => $username,
            'j_password' => $password,
            'Sign In'    => 'Sign In'
        )));
        curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie_file);
        curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie_file);
        $return = curl_exec($curl);
        if(preg_match("/Login failed\. Please check your username and password and try again\./", $return, $matches) !== 0) {
            throw new Exception('WRONG CREDENTIALS BITCH TITS');
        } else {
            $this->_success = true;
        }
    }
    /**
     * Returns the status of $this->loginAndGetCookie()
     * 
     * @return bool
     */
    public function didItWork() { return $this->_success; }
    /**
     * Scrape the printer friendly journey data and create a nice little array
     * so that we can pretend tfl aren't total wankers and gave us access to a nice
     * little api
     * 
     * @return array
     */
    public function getJourney() {
        if($this->_success === true) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_URL, 'https://oyster.tfl.gov.uk/oyster/journeyDetailsPrint.do');
            curl_setopt($curl, CURLOPT_REFERER, 'https://oyster.tfl.gov.uk/oyster/journeyHistory.do');
            curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.0; en-US; rv:1.4) Gecko/20030624 Netscape/7.1 (ax)");
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, '');
            curl_setopt($curl, CURLOPT_COOKIEFILE, $this->_cookie_file);
            curl_setopt($curl, CURLOPT_COOKIEJAR, $this->_cookie_file);
            $return = curl_exec($curl);
            if($return !== false) {
                $return = explode("\n", $return);
                $start = 0;
                $count = count($return);
                $end = $count - 4;
                $c = 0;
                for($i=0;$i<$count;$i++,$c++) {
                    $return[$i] = trim($return[$i]);
                    if(empty($return[$i])) {
                        unset($return[$i]);
                        $c--;
                    } else {
                        if(preg_match("/\<table\ class\=\"journeyhistory\"\>/", $return[$i]) !== 0) {
                            $start = $c;
                        }
                    }
                }
                $journey = array_slice($return, $start+10, -4, false);
                $data = array();
                $j = 0;
                $day = -1;
                $current = '';
                foreach($journey as $key => $value) {
                    $value = strip_tags($value);
                    if(preg_match("/[A-Za-z]{6,9}\, [0-9]{2} [A-Za-z]{3,9} [0-9]{4}/", $value, $matches) !== 0) {
                        $j = 0;
                        $day++;
                        $data[$day]['date'] = $matches[0];
                    } elseif(preg_match("/(\d[\d\,\.]+) daily total/", $value, $matches) !== 0) {
                        $data[$day]['total'] = $matches[1];
                    } elseif(preg_match("/([\d\:]*|\?\?\?\?+) - (\d[\d\:]+)/", $value, $matches) !== 0) {
                        $data[$day]['journeys'][$j]['time'] = array(
                            'start' => $matches[1],
                            'end'   => $matches[2]
                        );
                    } elseif(preg_match("/(.+) to (.+)/", $value, $matches) !== 0) {
                        $data[$day]['journeys'][$j]['stations'] = array(
                            'start' => $matches[1],
                            'end'   => $matches[2]
                        );
                    } elseif(preg_match("/&#163;(\d[\d\,\.]+)/", $value, $matches) !== 0) {
                        if(is_array($data[$day]['journeys'][$j])) {
                            if(!array_key_exists('charge', $data[$day]['journeys'][$j])) {
                                $data[$day]['journeys'][$j]['charge'] = $matches[1];
                            } elseif(!array_key_exists('balance', $data[$day]['journeys'][$j])) {
                                $data[$day]['journeys'][$j]['balance'] = $matches[1];
                                $j++;
                            }
                        }
                    }
                }
                $journeys = count($data);
                for($i=0;$i<$journeys;$i++) {
                    if(count($data[$i]['journeys']) == 0) {
                        unset($data[$i]);
                    } else {
                        if($this->_order_journey == 'desc') {
                            $data[$i]['journeys'] = array_reverse($data[$i]['journeys'], false);
                        }
                    }
                }
                if($this->_order_all == 'desc') {
                    $data = array_reverse($data, false);
                }
                return $data;
            }
        } else {
            return array();
        }
    }
    
}