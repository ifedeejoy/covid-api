<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EstimatorController extends Controller
{
    private $result; 

    public function __construct()
    {
        $this->result = null;
    }

    public function covid19ImpactEstimator(Request $request)
    {
        $name = $request->input('name'); 
        $avgAge = $request->input('avgAge'); 'avgAge';
        $avgIncome = $request->input('avgDailyIncomeInUSD');
        $avgPopulation = $request->input('avgDailyIncomePopulation');
        $periodType = $request->input('periodType');
        $timeToElapse = $request->input('timeToElapse');
        $reportedCases = $request->input('reportedCases');
        $population = $request->input('population');
        $totalBeds = $request->input('totalHospitalBeds');

        $input = array(
            "region" => array(
                "name"=> $name,
                "avgAge"=> $avgAge,
                "avgDailyIncomeInUSD"=> $avgIncome,
                "avgDailyIncomePopulation"=> $avgPopulation
            ),
            "periodType"=> $periodType,
            "timeToElapse"=> $timeToElapse,
            "reportedCases"=> $reportedCases,
            "population"=> $population,
            "totalHospitalBeds"=> $totalBeds
        );

        $impact = $this->impact($reportedCases, $periodType, $timeToElapse, $totalBeds, $avgIncome, $avgPopulation);
        $severe = $this->severe($reportedCases, $periodType, $timeToElapse, $totalBeds, $avgIncome, $avgPopulation);
        $data = array("data" => $input, "impact" => $impact, "severeImpact" => $severe);
        if($request->path() == "api/v1/on-covid-19" || $request->path() == "api/v1/on-covid-19/json"):
            $this->result =  response(json_encode($data), 200)->header('Content-Type', "application/json");
            return $this->result;
        elseif($request->path() == "api/v1/on-covid-19/xml"):
            $this->result = $this->xmlResponse($data)->header('Content-Type', "application/xml");
            return $this->result;
        endif;
    }

    public function xmlResponse($data)
    {
        $result = $this->toXML($data);
        return response($result, 200);
    }

    public function toXML($array, $rootElement = null, $xml = null) { 
        $_xml = $xml; 
          
        // If there is no Root Element then insert root 
        if ($_xml === null) { 
            $_xml = new \SimpleXMLElement($rootElement !== null ? $rootElement : '<root/>'); 
        } 
          
        // Visit all key value pair 
        foreach ($array as $k => $v) { 
              
            // If there is nested array then 
            if (is_array($v)) {  
                  
                // Call function for nested array 
                $this->toXML($v, $k, $_xml->addChild($k)); 
                } 
                  
            else { 
                  
                // Simply add child element.  
                $_xml->addChild($k, $v); 
            } 
        } 
          
        return $_xml->asXML(); 
    } 

    public function impact($reportedCases, $periodType, $timeToElapse, $totalBeds, $avgIncome, $avgPopulation)
    {
        $impactCI = $this->currentlyInfected($reportedCases, 10);
        $infectionsByTime = $this->infectionsByTime($impactCI, $periodType, $timeToElapse);
        $severeByTime = $infectionsByTime * 0.15;
        $bedsByTime = $this->bedsByTime($severeByTime, $totalBeds);
        $icuCases = $infectionsByTime * 0.05;
        $ventilatorCases = $infectionsByTime * 0.02;
        $dollarsInFlight = $this->dollarsInFlight($infectionsByTime, $avgPopulation, $avgIncome, $timeToElapse, $periodType);

        $impact = array(
            "currentlyInfected" => $impactCI,
            "infectionsByRequestedTime" => (int) $infectionsByTime,
            "severeCasesByRequestedTime" => (int) $severeByTime,
            "hospitalBedsByRequestedTime" => (int) $bedsByTime,
            "casesForICUByRequestedTime" => (int) $icuCases,
            "casesForVentilatorsByRequestedTime" => (int) $ventilatorCases,
            "dollarsInFlight" => (int) $dollarsInFlight
        );
        return $impact;
    }

    public function severe($reportedCases, $periodType, $timeToElapse, $totalBeds, $avgIncome, $avgPopulation)
    {
        $severeCI = $this->currentlyInfected($reportedCases, 50);
        $infectionsByTime = $this->infectionsByTime($severeCI, $periodType, $timeToElapse);
        $severeByTime = $infectionsByTime * 0.15;
        $bedsByTime = $this->bedsByTime($severeByTime, $totalBeds);
        $icuCases = $infectionsByTime * 0.05;
        $ventilatorCases = $infectionsByTime * 0.02;
        $dollarsInFlight = $this->dollarsInFlight($infectionsByTime, $avgPopulation, $avgIncome, $timeToElapse, $periodType);

        $severeImpact = array(
            "currentlyInfected" => $severeCI,
            "infectionsByRequestedTime" => (int) $infectionsByTime,
            "severeCasesByRequestedTime" => (int) $severeByTime,
            "hospitalBedsByRequestedTime" => (int) $bedsByTime,
            "casesForICUByRequestedTime" => (int) $icuCases,
            "casesForVentilatorsByRequestedTime" => (int) $ventilatorCases,
            "dollarsInFlight" => (int) $dollarsInFlight
        );
        return $severeImpact;
    }

    public function timeElapsed($periodType, $timeToElapse)
    {
        if($periodType == "days"):
            $period = $timeToElapse;
        elseif($periodType == "weeks"):
            $period = $timeToElapse * 7;
        elseif ($periodType == "months"):
            $period = $timeToElapse * 30;
        endif;
        return $period;
    }

    public function currentlyInfected($reportedCases, $multiplier)
    {
        $currentlyInfected = $reportedCases * $multiplier;
        return $currentlyInfected;
    }

    public function infectionsByTime($currentlyInfected, $periodType, $timetoElapse)
    {
        $factor = (int)($this->timeElapsed($periodType, $timetoElapse) / 3);
        $result = $currentlyInfected * pow(2, $factor);
        return $result;
    }


    public function bedsByTime($severeByTime, $totalBeds)
    {
        $availableBeds = $totalBeds * 0.35;
        $bedsByTime = $availableBeds - $severeByTime;
        return $bedsByTime;
    }

    public function dollarsInFlight($infectionsByTime, $avgPopulation, $avgIncome, $timeToElapse, $periodType)
    {
        $period = $this->timeElapsed($periodType, $timeToElapse);
        $result = (($infectionsByTime * $avgPopulation * $avgIncome)/$period);
        return $result;
    }
}


