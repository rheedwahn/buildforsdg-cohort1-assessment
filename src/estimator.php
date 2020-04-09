<?php

const ESTIMATED_DAYS = 30;

const PERCENTAGE_SEVERE_CASE_ESTIMATE = 15;

const PERCENTAGE_HOSPITAL_BED_ESTIMATE = 35;

const PERCENTAGE_ICU = 5;

const PERCENTAGE_VENTILATOR = 2;

function covid19ImpactEstimator($data)
{
    return formatOutput($data);
}

function formatOutput($data)
{
    return [
        'data' => $data,
        'impact' => [
            'currentlyInfected' => $currently_infected = $data['reportedCases'] * 10,
            'infectionsByRequestedTime' => $infection_impact = calculateInfectionByRequestedTime(
                                            $currently_infected,
                                            $data['periodType'], $data['timeToElapse']),
            'severeCasesByRequestedTime' => $severe_cases = calculateSevereCasesByRequestedTime($infection_impact),
            'hospitalBedsByRequestedTime' => calculateTotalHospitalBedForCovid19($data['totalHospitalBeds'], $severe_cases),
            'casesForICUByRequestedTime' => casesForICUByRequestedTime($infection_impact),
            'casesForVentilatorsByRequestedTime' => casesForVentilatorsByRequestedTime($infection_impact),
            'dollarsInFlight' => dollarsInFlight($infection_impact, $data['region']['avgDailyIncomeInUSD'],
                                                                    $data['region']['avgDailyIncomePopulation'])
        ],
        'severeImpact' => [
            'currentlyInfected' => $currently_infected = $data['reportedCases'] * 50,
            'infectionsByRequestedTime' => $infection_impact = calculateInfectionByRequestedTime(
                                            $currently_infected,
                                            $data['periodType'], $data['timeToElapse']),
            'severeCasesByRequestedTime' => $severe_cases = calculateSevereCasesByRequestedTime($infection_impact),
            'hospitalBedsByRequestedTime' => calculateTotalHospitalBedForCovid19($data['totalHospitalBeds'], $severe_cases),
            'casesForICUByRequestedTime' => casesForICUByRequestedTime($infection_impact),
            'casesForVentilatorsByRequestedTime' => casesForVentilatorsByRequestedTime($infection_impact),
            'dollarsInFlight' => dollarsInFlight($infection_impact, $data['region']['avgDailyIncomeInUSD'],
                                                                    $data['region']['avgDailyIncomePopulation'])
        ]
    ];
}

function calculateInfectionByRequestedTime($currently_infected, $period_type, $time_to_elapse)
{
    $factor = floor(normalizeTimeToElapse($period_type, $time_to_elapse) / 3);
    return $currently_infected * pow(2, $factor);
}

function normalizeTimeToElapse($period_type, $time_to_elapse)
{
    switch ($period_type) {
        case "months" :
            $normalised_time = $time_to_elapse * 30;
            break;
        case "weeks" :
            $normalised_time = $time_to_elapse * 7;
            break;
        default:
            $normalised_time = $time_to_elapse;
    }
    return $normalised_time;
}

function calculateSevereCasesByRequestedTime($infections_by_requested_time)
{
    return ceil(((PERCENTAGE_SEVERE_CASE_ESTIMATE / 100) * $infections_by_requested_time));
}

function calculateTotalHospitalBedForCovid19($hospital_beds, $severe_case)
{
    $available_bed_for_covid_19 = ceil(((PERCENTAGE_HOSPITAL_BED_ESTIMATE/100) * $hospital_beds));
    return $available_bed_for_covid_19 - $severe_case;
}

function casesForICUByRequestedTime($infections)
{
    return (PERCENTAGE_ICU/100) * $infections;
}

function casesForVentilatorsByRequestedTime($infections)
{
    return (PERCENTAGE_VENTILATOR/100) * $infections;
}

function dollarsInFlight($infection, $avg_daily_income, $avg_income_population)
{
    return $infection * $avg_daily_income * $avg_income_population * ESTIMATED_DAYS;
}
