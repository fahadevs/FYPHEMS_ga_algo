<?php

// Genetic Algorithm Parameters
$populationSize = 50;
$generationCount = 100;
$mutationRate = 0.1;
$totalsolarpower = 0.0;
global $totalappliancepower;
$appliancePower = [800, 400];
// Appliance Parameters
$applianceCount = 2;
$appliancePower = [1000, 1500]; // Initial power consumption for each appliance (for a full hour)

// Solar Power Forecast (example values)
$solarPowerForecast = [
    0, 0, 0, 0,  // Midnight
    0, 0, 0, 0,  // 1:00 AM
    0, 0, 0, 0,  // 2:00 AM
    0, 0, 0, 0,  // 3:00 AM
    0, 0, 0, 0,  // 4:00 AM
    0, 0, 0, 0,  // 5:00 AM (Morning)
    120, 130, 140, 150,  // 6:00 AM
    160, 170, 180, 190,  // 7:00 AM
    200, 200, 200, 200,  // 8:00 AM
    200, 200, 200, 200,  // 9:00 AM
    200, 200, 200, 200,  // 10:00 AM
    200, 200, 200, 200,  // 11:00 AM (Midday)
    200, 200, 200, 200,  // 12:00 PM
    200, 200, 200, 200,  // 1:00 PM
    200, 200, 200, 200,  // 2:00 PM
    200, 200, 200, 200,  // 3:00 PM
    200, 200, 200, 200,  // 4:00 PM (Afternoon)
    170, 160, 150, 140,  // 5:00 PM
    130, 120, 0, 0,      // 6:00 PM (Evening)
    0, 0, 0, 0,          // 7:00 PM
    0, 0, 0, 0,          // 8:00 PM
    0, 0, 0, 0,          // 9:00 PM
    0, 0, 0, 0,          // 10:00 PM
    0, 0, 0, 0           // 11:00 PM (Night)
];


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
printSchedule($bestSchedule, $appliancePower);

// Genetic Algorithm Functions

function generateRandomSchedule($applianceCount) {
    $combinedGenome = '';
    $timeSlots = 24 * 4; // 24 hours with 15-minute slots

    for ($slot = 0; $slot < $timeSlots; $slot++) {
        $activeAppliance = mt_rand(0, $applianceCount);
        $combinedGenome .= str_repeat('0', $activeAppliance) . '1' . str_repeat('0', $applianceCount - $activeAppliance);
    }

    return $combinedGenome;
}

function calculateFitness($schedule, $solarPowerForecast, $appliancePower) {
    $timeSlots = 24 * 4; // 24 hours with 15-minute slots
    $totalEnergyConsumption = 0;
    global $totalappliancepower;
    for ($slot = 0; $slot < $timeSlots; $slot++) {
        $slotEnergyConsumption = 0;

        for ($applianceIndex = 0; $applianceIndex < count($appliancePower); $applianceIndex++) {
            $appliancePowerForSlot = $appliancePower[$applianceIndex] / 4; // Divide by 4 for 15-minute slot
            if ($schedule[$applianceIndex * $timeSlots + $slot] == '1') {
                $slotEnergyConsumption += $appliancePowerForSlot;
            }
        }
        
        $totalsolarpower += $solarPowerForecast[$slot];
        $totalEnergyConsumption += $slotEnergyConsumption;
    }
    if($totalsolarpower> $totalappliancepower)
    {
        $fitness=($totalsolarpower-$totalappliancepower)/$totalEnergyConsumption;
    }
    else if($totalsolarpower<=$totalappliancepower)
    {
        $fitness=1-($totalEnergyConsumption/$solarPowerForecast);
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
        if ($fitness > $bestFitness && $fitness <= 1) {
            $bestFitness = $fitness;
            $bestSchedule = $schedule;
        }
    }

    return $bestSchedule;
}

function printSchedule($schedule, $appliancePower) {
    echo "Optimized Schedule: " . PHP_EOL;

    $timeSlots = 24 * 4; // 24 hours with 15-minute slots
    for ($slot = 0; $slot < $timeSlots; $slot++) {
        echo "Time Slot $slot: ";
        for ($applianceIndex = 0; $applianceIndex < count($appliancePower); $applianceIndex++) {
            if ($schedule[$applianceIndex * $timeSlots + $slot] == '1') {
                echo "Appliance " . ($applianceIndex + 1) . ", ";
            }
        }
        echo PHP_EOL;
    }
}

?>
