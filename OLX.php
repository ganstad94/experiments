<?php
// Quick and dirty OLX JSON API parser

// define JSON URLs to parse
$urls = array(
    'NG' => 'http://api-v2.olx.com/items?seo=false&abundance=true&languageId=1&pageSize=1&location=www.olx.com.ng&offset=0&platform=desktop',
    'UG' => 'http://api-v2.olx.com/items?seo=false&abundance=true&languageId=1&pageSize=1&location=www.olx.co.ug&offset=0&platform=desktop',
);

$result = array(); // here we'll store the stats
foreach ($urls as $country => $url) {
    // add empty array for the country
    $result[$country] = array(
        'total' => 0,
        'states' => array(),
        'categories' => array(),
    );
    
    // get the raw metadata array
    $mdata = getMetadata($url);
    if ($mdata !== false) {
        // parse stats and add to resultset by country
        if (checkIfValid($mdata)) {
            $result[$country]['total'] = $mdata['total']; // get the total for the country
            // get the numbers by cities/states
            $states = findArrayByName('state',$mdata['filters']);
            if ($states !== false) {
                foreach ($states as $key => $val) {
                    $result[$country]['states'][$val['value']] = $val['count'];
                }
            }
            // get the numbers by categories
            $categories = findArrayByName('parentcategory',$mdata['filters']);
            if ($categories !== false) {
                foreach ($categories as $key => $val) {
                    $result[$country]['categories'][$val['value']] = $val['count'];
                }
            }
        }
    }
}
output($result);


// helpers

// for extracting the 'metadata' element if it exists
function getMetadata($url) {
    $json = file_get_contents($url);
    $data = json_decode($json, true);
    if (!isset($data['metadata'])) return false;
    return $data['metadata'];
}

// for checking if overall JSON structure meets our expectations
function checkIfValid($source) {
    if (!isset($source['total'])) return false;
    if (!isset($source['filters'])) return false;
    if (!is_array($source['filters'])) return false;
    // add more checks as necessary...
    return true;
}

// to find the sub-arrays of cities/states and categories, as their indexes may change
function findArrayByName($name, $source) {
    foreach ($source as $key => $val) {
        if (!isset($val['name']) || !isset($val['value'])) break; // if the current array doesn't include the keys we need, take the next one
        if ($val['name'] === $name) {
            return $val['value']; // found it, return values
        }
    }
    return false;
}

function output($data) {
    // alternatively, prepare output to CSV or a database
    print '<pre>';
    print_r($data);
    print '</pre>';
}

?>