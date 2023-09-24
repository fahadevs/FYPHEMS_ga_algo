<?php

// Genetic Algorithm Parameters
$populationSize = 50;
$generationCount = 100;
$mutationRate = 0.1;
$totalsolarpower=0.0;

// Appliance Parameters
$applianceCount = 2;
$appliancePower = [1000, 1500]; // Power consumption for each appliance

// Solar Power Forecast (example values)
$solarPowerForecast = [0, 0, 0, 0, 0,0, 900, 1200, 1600, 2000, 2200, 2400,
                       2600, 2800, 3000, 3200, 3400, 3600, 3800, 0, 0, 0, 0, 0];

// Generate Initial Population
$population = [];
for ($i = 0; $i < $populationSize; $i++) {
    $schedule = generateRandomSchedule($applianceCount);
    $population[] = $schedule;
}

// Genetic Algorithm Main Loop
for ($generation = 1; $generation <= $generationCount; $generation++) {
    $fitnessScores = [];
    foreach ($population as $schedule) {
        $fitnessScores[] = calculateFitness($schedule, $solarPowerForecast, $appliancePower);
    }

    $newPopulation = [];
    for ($i = 0; $i < $populationSize; $i++) {
        $parent1 = selectParent($population, $fitnessScores);
        $parent2 = selectParent($population, $fitnessScores);
        $offspring = crossover($parent1, $parent2);
        mutate($offspring);
        $newPopulation[] = $offspring;
    }

    $population = $newPopulation;
}

// Find the best schedule in the final population
$bestSchedule = findBestSchedule($population, $solarPowerForecast, $appliancePower);
printSchedule($bestSchedule,$appliancePower);

// Genetic Algorithm Functions

function generateRandomSchedule($applianceCount) {
    $schedule = '';
    for ($i = 0; $i < 24 * $applianceCount; $i++) {
        $schedule .= mt_rand(0, 1);
    }
    return $schedule;
}

function calculateFitness($schedule, $solarPowerForecast, $appliancePower) {
    $totalEnergyConsumption = 0;

    for ($slot = 0; $slot < 24; $slot++) {
        $slotEnergyConsumption = 0;

        for ($applianceIndex = 0; $applianceIndex < count($appliancePower); $applianceIndex++) {
            if ($schedule[$applianceIndex * 24 + $slot] == '1') {
                $slotEnergyConsumption += $appliancePower[$applianceIndex];
            }
        }
        $totalsolarpower += $solarPowerForecast[$slot];
        $totalEnergyConsumption += $slotEnergyConsumption;
    }
    if ($totalEnergyConsumption <= $totalsolarpower) {
        
        $fitness = $totalEnergyConsumption / $totalsolarpower;
        
    } else {
        $fitness = $totalsolarpower / $totalEnergyConsumption;
    }

    return $fitness;
}

function selectParent($population, $fitnessScores) {
    $totalFitness = array_sum($fitnessScores);
    $randomValue = mt_rand(0, $totalFitness);

    $cumulativeFitness = 0;
    foreach ($population as $index => $schedule) {
        $cumulativeFitness += $fitnessScores[$index];
        if ($cumulativeFitness >= $randomValue) {
            return $schedule;
        }
    }

    return $population[count($population) - 1];
}

function crossover($parent1, $parent2) {
    $crossoverPoint = mt_rand(1, strlen($parent1) - 1);
    $offspring = substr($parent1, 0, $crossoverPoint) . substr($parent2, $crossoverPoint);
    return $offspring;
}

function mutate(&$schedule) {
    global $mutationRate;
    for ($i = 0; $i < strlen($schedule); $i++) {
        if (mt_rand(0, 100) / 100 < $mutationRate) {
            $schedule[$i] = ($schedule[$i] == '0') ? '1' : '0';
        }
    }
}

function findBestSchedule($population, $solarPowerForecast, $appliancePower) {
    $bestSchedule = $population[0];
    $bestFitness = calculateFitness($bestSchedule, $solarPowerForecast, $appliancePower);

    foreach ($population as $schedule) {
        $fitness = calculateFitness($schedule, $solarPowerForecast, $appliancePower);
        if ($fitness > $bestFitness) {
            $bestFitness = $fitness;
            $bestSchedule = $schedule;
        }
    }

    return $bestSchedule;
}

function printSchedule($schedule,$appliancePower) {
    echo "Optimized Schedule: " . PHP_EOL;

    for ($slot = 0; $slot < 24; $slot++) {
        echo "Time Slot $slot: ";
        for ($applianceIndex = 0; $applianceIndex < count($appliancePower); $applianceIndex++) {
            if ($schedule[$applianceIndex * 24 + $slot] == '1') {
                echo "Appliance " . ($applianceIndex + 1) . ", ";
            }
        }
        echo PHP_EOL;
    }
}

?>
