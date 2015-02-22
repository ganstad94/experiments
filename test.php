<?php
require_once('OLX.php');

$parser = new OLXParser();
$parser->addURL('NG', 'http://api-v2.olx.com/items?seo=false&abundance=true&languageId=1&pageSize=1&location=www.olx.com.ng&offset=0&platform=desktop');
$parser->addURL('UG', 'http://api-v2.olx.com/items?seo=false&abundance=true&languageId=1&pageSize=1&location=www.olx.co.ug&offset=0&platform=desktop');
$parser->parse();

/**
* Output examples:
*/

// limited CSV output for NG
$parser->setLimits(3,3);
$parser->output('NG','csv');
print '<br /><br />';

// full JSON output
$parser->setLimits();
$parser->output('','json');
print '<br /><br />';

// limited JSON output for UG
$parser->setLimits(1,3);
$parser->output('UG','json');
print '<br /><br />';

// non-existant country code
$parser->output('BB','csv');
print '<br /><br />';
$parser->output('ZZ','json');
print '<br /><br />';

?>