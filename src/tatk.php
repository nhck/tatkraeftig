<?php
/**
 * @package Tatkraeftig
 * @version 1
 */
/*
Plugin Name: Tatkraeftig Project loader
Depends:WordPress-to-Lead for Salesforce CRM
Plugin URI: http://tatkraeftig.org/
Description: Plugin loads http://tatkraeftig.org/ projects
Author: RHOKHH
Version: 1
Author URI: http://rhokhh.org
*/

/**
* File Structure:
* 1) Functionality
* 2) Handlers
*/



require_once( ABSPATH . 'wp-content/plugins/salesforce-wordpress-to-lead/salesforce.php' );

/*
 * Class 
 * Provides Wrapper, so functions can have human names
 */
class tatkraeftig {

  var $oauthToken;

  function __construct() {

    add_shortcode('projekte', array($this, 'display'));

  }

  function login() {
    
    require_once("settings.php");

    $url = 'https://login.salesforce.com/services/oauth2/token';
    $args = array(
        'method' => 'POST',
        'body' => array(
            'grant_type' => 'password',
            'username' => $username,
            'password' => $password,
            'client_id' => $client_id,
            'client_secret' => $client_secret
        ));

    $request = wp_remote_post( $url, $args );
    $requestJson = json_decode($request['body'], true);
    $this->oauthToken = $requestJson['access_token'];
  }

  /*
   * 
   * Function gets Data from somewhere via rest api
   * 
   * @param none
   * @return array
   */
	function getCampaigns() {

    if(empty($this->oauthToken)) {
      $this->login();
    }

    $urlParams = urlencode('SELECT Name, Id, Description from Campaign');
    $url = 'https://eu2.salesforce.com/services/data/v29.0/query?q=' . $urlParams;
    $args = array(
          'method' => 'GET',
          'headers' => array( 'Authorization:' => 'Bearer ' . $this->oauthToken )
        );
    
    $request = wp_remote_get( $url, $args );
    $requestJson = json_decode($request['body'], true);
    
    return $requestJson;
  }
	
  /*
   * Prints Array retrieved via {@see getdata}
   * 
   * @param array Array that was retrieved
   * @return string
   */
  function printdata($data = array()) {
    
    return 'hello';
  }

  function createForm($campaignID) {
    $options = get_option('salesforce2');
    $options['forms'][1]['inputs']['Campaign_ID']['value'] = $campaignID; 
    return salesforce_form($options) . '<pre>' . print_r($options, true) . '</pre>';
  }

  function display(){
    $campaignsAsJson = $this->getCampaigns();
    $result_array = array();
    foreach ($campaignsAsJson["records"] as $key => $value) {
      $tmp = $value["Name"] . $value["Description"];
        if (isset($_POST['w2lsubmit']) && $_POST['Campaign_ID'] == $value["Id"]) {
          $tmp = $tmp . "success";
          $post = array();
          $post[$id] = trim(strip_tags(stripslashes($_POST[$id])));
          $options = get_option("salesforce2");
          submit_salesforce_form($post, $options, 1);

        } else {
          $tmp = $tmp . $this->createForm($value["Id"]);
        }

      $result_array[] = $tmp;
    }
    return implode($result_array);
  }
}

//Register Handlers
$tatk = new tatkraeftig;



