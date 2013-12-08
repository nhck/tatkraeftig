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

    #add_action('wp_enqueue_scripts', 'addScripts');
    wp_enqueue_script( 'tatkraeftig', plugins_url('/js/tatkraeftig.js', __FILE__), array(), '1.0.0', true );
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

    $urlParams = urlencode('SELECT Id, Name, Description, StartDate, EndDate, IsActive, NumberOfLeads, Uhrzeit_von_bis__c, Treffpunkt__c, Aufgaben__c, Freiwillige__c FROM Campaign WHERE IsActive=true AND EndDate >= TODAY');
    $url = 'https://eu2.salesforce.com/services/data/v29.0/query?q=' . $urlParams;
    $args = array(
      'method' => 'GET',
      'headers' => array('Authorization:' => 'Bearer ' . $this->oauthToken)
    );

    $request = wp_remote_get($url, $args);
    $requestJson = json_decode($request['body'], true);

    return $requestJson;
  }

  function createForm($campaignID) {
    $options = get_option('salesforce2');
    $options['forms'][1]['inputs']['Campaign_ID']['value'] = $campaignID;

    #'<pre><h4>$options</h4>' . print_r($options, true) . '</pre>'
    #'<pre><h4>Submitted Options</h4>' . print_r($_POST, true) . '</pre>'

    return salesforce_form($options);
  }

  function submission() {
    $options = get_option("salesforce2");

    //very basic validation
    foreach ($options['forms'][1]['inputs'] as $key => $input) {
        if (isset($_POST[$key])) {
          $post[$key] = trim(strip_tags(stripslashes($_POST[$key])));
        }
    }
    $result = submit_salesforce_form($post, $options);

    if (!$result) {
      $content = '<strong>' . esc_html(stripslashes($options['sferrormsg'])) . '</strong>';
    }
    else {
      $content = '<h3>Danke<h3><p>Du wurdest erfolgreich angemeldet.</p><p><b><a href="">Zurück</b></p>';
    }

    return $content;
  }

  function renderProjectList() {
    $campaignsAsJson = $this->getCampaigns();
    $result_array = array();
    
    setlocale(LC_time, 'de_DE@euro', 'de_DE', 'deu_deu'); //Set locale for time display
    
    foreach ($campaignsAsJson["records"] as $key => $value) {
      $result = array();
      $result[] = "<h2 class='entry-title'>" . $value["Name"] . "</h2>";
      $result[] = "<p>" . $value["Description"] . "</p>";
      $result[] = "<table>";
      $result[] = "<tr>";
      $result[] = "<td>Projektart: </td>";
      $result[] = "<td>" . $value["Aufgaben__c"] . "</td>";
      $result[] = "</tr>";
      $result[] = "<tr>";
      $result[] = "<td>Datum: </td>";
      $result[] = "<td>" . strftime ('%d. %b. %Y',strtotime($value["StartDate"])) . " bis " . strftime ('%d. %b. %Y',strtotime($value["EndDate"])) . "</td>";
      $result[] = "</tr>";
      $result[] = "<tr>";
      $result[] = "<td>Zeit: </td>";
      $result[] = "<td>" . $value["Uhrzeit_von_bis__c"] . "</td>";
      $result[] = "</tr>";
      $result[] = "<tr>";
      $result[] = "<td>Ort: </td>";
      $result[] = "<td>" . $value["Treffpunkt__c"] . "</td>";
      $result[] = "</tr>";
      $result[] = "<tr>";
      $result[] = "<td>Gesucht: </td>";
      $result[] = "<td>" . $value["Freiwillige__c"] . "</td>";
      $result[] = "</tr>";
      $result[] = "</table>";
      $result[]  = "<div>";
      $result[]  = "<img class='submit-button' src='https://gallery.mailchimp.com/91eaafc344593897c12af670b/images/anmelde_button.png' width='120' height='43' align='none'>";
      $result[]  = "<div class='project-form' style='display:none'>" . $this->createForm($value["Id"]) . "</div>";
      $result[]  = "</div>";
      
      $result_array[] = '<div>' . implode($result) . '</div>';
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



