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

    $request = wp_remote_post($url, $args);
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

    if (empty($this->oauthToken)) {
      $this->login();
    }

    $urlParams = urlencode('SELECT Name, Id, Description from Campaign');
    $url = 'https://eu2.salesforce.com/services/data/v29.0/query?q=' . $urlParams;
    $args = array(
      'method' => 'GET',
      'headers' => array('Authorization:' => 'Bearer ' . $this->oauthToken)
    );

    $request = wp_remote_get($url, $args);
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
    return salesforce_form($options) . '<pre><h4>$options</h4>' . print_r($options, true) . '</pre>' . '<pre><h4>Submitted Options</h4>' . print_r($_POST, true) . '</pre>';
  }

  function submission() {
    $options = get_option("salesforce2");

    //validation
    foreach ($options['forms'][1]['inputs'] as $key => $input) {

      $val = trim($_POST[$key]);
      if ($input['required'] && !$val) {
        $options['forms'][$form]['inputs'][$key]['error'] = true;
        $error = true;
      }
      else if ($key == 'email' && $input['required'] && !is_email($_POST[$key])) {
        $error = true;
        $emailerror = true;
      }
      else {
        if (isset($_POST[$id]))
          $post[$key] = trim(strip_tags(stripslashes($_POST[$key])));
      }
    }

    $result = submit_salesforce_form($post, $options);

    if (!$result) {
      $content = '<strong>' . esc_html(stripslashes($options['sferrormsg'])) . '</strong>';
    }
    else {
      $content = 'Danke';
    }

    return $content;
  }

  function renderProjectList() {
    $campaignsAsJson = $this->getCampaigns();
    $result_array = array();
    foreach ($campaignsAsJson["records"] as $key => $value) {
      $result = array();
      $result[] = $value["Name"];
      $result[] = $value["Description"];

     
        $result[] = $this->createForm($value["Id"]);
      $result_array[] = implode($result);
    }
    return implode($result_array);
  }

  function display() {
    if (isset($_POST['w2lsubmit'])) {
      $content = $this->submission();
    }
    else {
      $content = $this->renderProjectList();
    }
    return $content;
  }

}

//Register Handlers
$tatk = new tatkraeftig;



