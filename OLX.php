<?php

class OLXParser {
    private $result; // stores parsed data
    private $urls; // stores URLs to parse
    private $limits; // stores limits set for cities and categories
    
    // initialize vars
    public function __construct()
    {
        $this->result = array();
        $this->urls = array();
        $this->limits = array();
        $this->setLimits();
    }
    
    
    /**
    * All methods related to settings
    */
    
    // adds an URL to class var, identified by country code
    public function addURL($countryCode, $url)
    {
        $this->urls[$countryCode] = $url;
        return true;
    }
    
    // removes an URL from class var, identified by country code
    public function removeURL($countryCode)
    {
        if (array_key_exists($countryCode, $this->urls)) {
            unset($this->urls[$countryCode]);
            return true;
        }
        return false;
    }
    
    // sets the number of cities and categories to include in output when output() is called
    // 0 = unlimited, outputs full lists
    public function setLimits($cities=0, $categories=0)
    {
        // should check if >=0 and a number
        $this->limits['cities'] = $cities;
        $this->limits['categories'] = $categories;
    }
    
    
    /**
    * All related to source JSON parsing
    */
    
    // loops through JSON API URLs, calls parser, checks source integrity and adds the resulting data to class var
    // returns nothing. could potentially return true/false for success or failure
    public function parse()
    {
        $this->result = array(); // reset the resultset when parse() is called
        if (sizeof($this->urls) > 0) {
            foreach ($this->urls as $country => $url) {
                // add empty array for the country
                $this->result[$country] = array(
                    'total' => 0,
                    'states' => array(),
                    'categories' => array(),
                );
                // get the raw metadata array
                $mdata = $this->getMetadata($url);
                if ($mdata !== false) {
                    // parse stats and add to resultset by country
                    if ($this->checkIfValid($mdata)) {
                        $this->result[$country]['total'] = $mdata['total']; // get the total for the country
                        // get the numbers by cities/states
                        $states = $this->findArrayByName('state',$mdata['filters']);
                        if ($states !== false) {
                            foreach ($states as $key => $val) {
                                $this->result[$country]['states'][$val['value']] = $val['count'];
                            }
                        }
                        // get the numbers by categories
                        $categories = $this->findArrayByName('parentcategory',$mdata['filters']);
                        if ($categories !== false) {
                            foreach ($categories as $key => $val) {
                                $this->result[$country]['categories'][$val['value']] = $val['count'];
                            }
                        }
                    }
                }
            }
        }
    }
    
    // extracts the 'metadata' element if it exists
    private function getMetadata($url)
    {
        $json = file_get_contents($url);
        $data = json_decode($json, true);
        if (!isset($data['metadata'])) return false;
        return $data['metadata'];
    }
    
    // checks if overall JSON structure meets our expectations
    private function checkIfValid($source)
    {
        if (!isset($source['total'])) return false;
        if (!isset($source['filters'])) return false;
        if (!is_array($source['filters'])) return false;
        // add more checks as necessary...
        return true;
    }
    
    // finds the sub-arrays of cities/states and categories, as their indexes may change
    private function findArrayByName($name, $source)
    {
        foreach ($source as $key => $val) {
            if (!isset($val['name']) || !isset($val['value'])) break; // if the current array doesn't include the keys we need, take the next one
            if ($val['name'] === $name) {
                return $val['value']; // found it, return values
            }
        }
        return false;
    }
    
    
    /**
    * All related to output formatting
    */
    
    // public proxy method to private output formatting according to requested type
    public function output($countryCode='',$type='json')
    {
        print $this->formatCountryOutput($countryCode, $type);
    }
    
    // builds the complete JSON/CSV string by country (if specified) or all countries
    private function formatCountryOutput($countryCode='',$type)
    {
        $res = '';
        switch ($type) {
            case 'csv':
                if ($countryCode !== '') {
                    $res = '"no data for country '.$countryCode.'"';
                    if (array_key_exists($countryCode, $this->result)) {
                        $res = implode("\n",$this->buildCountryArrayCSV($countryCode));
                    }
                } else {
                    // loop through countries
                    $tmp = array();
                    foreach ($this->result as $key => $val) {
                        array_push($tmp, implode("\n",$this->buildCountryArrayCSV($key)));
                        $res = implode("\n\n",$tmp);
                    }
                }
            break;
            default: // json
                if ($countryCode !== '') {
                    $res = '{"error":"no data for country '.$countryCode.'"}';
                    if (array_key_exists($countryCode, $this->result)) {
                        $res = json_encode($this->buildCountryArrayJSON($countryCode));
                    }
                } else {
                    // loop through countries
                    $tmp = array();
                    foreach ($this->result as $key => $val) {
                        $tmp = array_merge($tmp, $this->buildCountryArrayJSON($key));
                    }
                    $res = json_encode($tmp);
                }
            break;
        }
        return $res;
    }
    
    // gets the data for a country, obeying the limits set, returns json-ready array
    private function buildCountryArrayJSON($countryCode)
    {
        $res = array();
        $res[$countryCode] = array(
            'total' => $this->getTotalByCountry($countryCode),
            'states' => $this->getStatesByCountry($countryCode),
            'categories' => $this->getCategoriesByCountry($countryCode),
        );
        return $res;
    }
    
    // gets the data for a country, obeying the limits set, returns csv-ready array
    private function buildCountryArrayCSV($countryCode)
    {
        $res = array(
            'labels' => '"' . $countryCode . '_total"',
            'values' => $this->getTotalByCountry($countryCode),
        );
        $states = $this->getStatesByCountry($countryCode);
        foreach ($states as $name => $count) {
            $res['labels'] .= ',"' . $countryCode . '_state_' . $name . '"';
            $res['values'] .= ',' . $count;
        }
        $categories = $this->getCategoriesByCountry($countryCode);
        foreach ($categories as $name => $count) {
            $res['labels'] .= ',"' . $countryCode . '_category_' . $name . '"';
            $res['values'] .= ',' . $count;
        }
        return $res;
    }
    
    // returns the total for a country
    private function getTotalByCountry($countryCode)
    {
        return $this->result[$countryCode]['total'];
    }
    
    // returns states for a country, obeying limits set
    private function getStatesByCountry($countryCode)
    {
        if ($this->limits['cities'] > 0) {
            return array_slice($this->result[$countryCode]['states'], 0, $this->limits['cities']);
        } else {
            return $this->result[$countryCode]['states'];
        }
    }
    
    // returns categories for a country, obeying limits set
    private function getCategoriesByCountry($countryCode)
    {
        if ($this->limits['categories'] > 0) {
            return array_slice($this->result[$countryCode]['categories'], 0, $this->limits['categories']);
        } else {
            return $this->result[$countryCode]['categories'];
        }
    }
    
}

?>