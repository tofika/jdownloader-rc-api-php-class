<?php

/*
 * JDownloader RemoteControl API class definition
 * https://github.com/tofika/jdownloader-rc-api-php-class
 *
 * @author Anatoliy Kultenko "tofik"
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 */

class JDRCAPI
{
    private $j_api_url;
    private $j_autoadding;
    private $j_startafteradding;
    public  $j_api_ver = '1.0.28032013';

    public function __construct( $url) {
        $this -> j_api_url = $url;
        if( $this -> j_check_rcversion()) {
            if( !$this -> j_set_autoadding( 'false')) $this -> j_autoadding = 'undefined';
            if( !$this -> j_set_startafteradding( 'true')) $this -> j_startafteradding = 'undefined';
        }
    }

    private function j_wait_grabber() {
        $wait_limit = 60;
        $time = 0;
        //step 1: j_wait_grabber_processing()
        while( $this -> j_get_grabber_isbusy() && ($time < $wait_limit)) { $time++; sleep( 1); }
        if( $time == $wait_limit) return false;
        sleep( 3);
        //step 2: j_wait_grabber_checking()
        while( (strpos( $this -> j_get_grabber_list(), "package_name=\"Unchecked\"") !== false) && ($time < $wait_limit)) { $time++; sleep( 1); }
        if( $time == $wait_limit) return false;
        //step 3: j_wait_grabber_processing()
        while( $this -> j_get_grabber_isbusy() && ($time < $wait_limit)) { $time++; sleep( 1); }
        //step 4: check if grabber list is empty
        if( strpos( $this -> j_get_grabber_list(), "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>\r\n<jdownloader/>") !== false) return false;
        //step 5: check if time is over
        if( $time == $wait_limit) return false; else return true;
    }

    private function j_check_rcversion() {
        $url_path = $this -> j_api_url.'/get/rcversion';
        $response = $this -> j_api_query( $url_path);
        if( $response['code'] == 200 && isset( $response['text']) && $response['text'] == '12612') return true; else return false;
    }

    private function j_set_autoadding( $flag = 'false') {
        $url_path = $this -> j_api_url.'/set/grabber/autoadding/'.$flag;
        $response = $this -> j_api_query( $url_path);
        if( $response['code'] == 200 && (strpos( $response['text'], 'PARAM_START_AFTER_ADDING_LINKS_AUTO=') !== false)) return true; else return false;
    }

    private function j_set_startafteradding( $flag = 'false') {
        $url_path = $this -> j_api_url.'/set/grabber/startafteradding/'.$flag;
        $response = $this -> j_api_query( $url_path);
        if( $response['code'] == 200 && (strpos( $response['text'], 'PARAM_START_AFTER_ADDING_LINKS=') !== false)) return true; else return false;
    }

    private function j_get_grabber_isbusy() {
        $url_path = $this -> j_api_url.'/get/grabber/isbusy';
        $response = $this -> j_api_query( $url_path);
        if( $response['code'] == 200 && $response['text'] == 'true') return true; else return false;
    }

    private function j_action_start() {
        $url_path = $this -> j_api_url.'/action/start';
        $response = $this -> j_api_query( $url_path);
        if( $response['code'] == 200 && $response['text'] == 'Downloads started') return true; else return false;
    }

    private function j_action_add_links( $links) {
        $url_path = $this -> j_api_url.'/action/add/links/'.urlencode( $links);
        $response = $this -> j_api_query( $url_path);
        if( $response['code'] == 200) return true; else return false;
    }

    private function j_action_grabber_move( $package_name, $links) {
        $url_path = $this -> j_api_url."/action/grabber/move/".urlencode( $package_name)."/".urlencode( $links);
        $response = $this -> j_api_query( $url_path);
        if( $response['code'] == 200 && $response['text'] == 'No links moved - check input.') return false; else return true;
    }

    private function j_action_grabber_remove( $package_name) {
        $url_path = $this -> j_api_url."/action/grabber/remove/".urlencode( $package_name);
        $response = $this -> j_api_query( $url_path);
        if( $response['code'] == 200 && $response['text'] != "The following packages were removed from grabber: '$package_name'") return false; else return true;
    }

    private function j_action_grabber_confirm( $package_name) {
        $url_path = $this -> j_api_url."/action/grabber/confirm/".urlencode( $package_name);
        $response = $this -> j_api_query( $url_path);
        if( $response['code'] == 200 && $response['text'] != "The following packages are now scheduled for download: '$package_name'") return false; else return true;
    }

    private function j_get_grabber_list() {
        $url_path = $this -> j_api_url.'/get/grabber/list';
        $response = $this -> j_api_query( $url_path);
        if( $response['code'] == 200) return $response['text']; else return false;
    }

    private function j_api_query( $url) {
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url);
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
        $response = array();
        $response['text'] = curl_exec( $ch);
        $response['code'] = curl_getinfo( $ch, CURLINFO_HTTP_CODE);
        curl_close( $ch);
        return $response;
    }

    public function j_add_links( $package_name, $links) //$package_name - string, $links - array or string with delimiter "\n"
    {
        if( is_array( $links)) { $links = implode( "\n", $links); }
        if( is_string( $links)) { if( strpos( $links, " ")) { return false; } }
        if( strpos( $this -> j_get_grabber_list(), "file_available=\"FALSE\"") !== false) {
            $this -> j_action_grabber_remove( "Offline");
        }
        if( !$this -> j_action_add_links( $links)) return false;
        if( !$this -> j_wait_grabber()) return false;
        if( strpos( $this -> j_get_grabber_list(), "file_available=\"FALSE\"") !== false) {
            $this -> j_action_grabber_remove( "Offline");
            return false;
        }
        if( !$this -> j_action_grabber_move( $package_name, $links)) return false;
        if( !$this -> j_action_grabber_confirm( $package_name)) return false;
        if( !$this -> j_action_start()) return false;
        return true;
    }

    public function j_is_online() {
        return $this -> j_check_rcversion();
    }
}

?>
