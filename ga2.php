<?php
// Genetic Algorithm Parameters
$populationSize = 1000;
$generationCount = 200;
$mutationRate = 0.03;
global $appliancePower, $applianceDuration,$totalAppliancePower,$totalAppliancePowerby15min;
// Appliance Parameters

$appliancePower = [1000, 1500,500,1000];
$applianceCount = count($appliancePower);
$applianceDuration = [30,45,15,30];
for($i=0; $i<count($appliancePower); $i++ )
{
    $totalAppliancePower+=$appliancePower[$i];
    $appliancePower[$i]=($appliancePower[$i]*0.25);
    $totalAppliancePowerby15min[$i]=$appliancePower[$i]*($applianceDuration[$i]/15);
    echo "total appliance power by 15 min :  " . $totalAppliancePowerby15min[$i] . "<br>";  
    echo " appliance" . $i. "power in 15 min:----" . $appliancePower[$i] . " ,";
}

// Solar Power Forecast (example values)
$solarPowerForecast = [
    0, 0, 0, 0,  // Midnight
    0, 0, 0, 0,  // 1:00 AM
    0, 0, 0, 0,  // 2:00 AM
    0, 0, 0, 0,  // 3:00 AM
    0, 0, 0, 0,  // 4:00 AM
    0, 0, 0, 0,  // 5:00 AM (Morning)21
    1200, 1300, 1400, 1500,  // 6:00 AM
    1600, 1700, 1800, 1900,  // 7:00 AM
    2000, 2000, 2000, 2000,  // 8:00 AM
    2000, 2000, 02000, 2000,  // 9:00 AM
    2000, 2000, 2000, 2000,  // 10:00 AM
    2000, 2000, 2000, 2000,  // 11:00 AM (Midday)
    2000, 2000, 2000, 2000,  // 12:00 PM
    2000, 2000, 2000, 2000,  // 1:00 PM
    2000, 2000, 2000, 2000,  // 2:00 PM
    2000, 2000, 2000, 2000,  // 3:00 PM
    2000, 2000, 2000, 2000,  // 4:00 PM (Afternoon)
    1700, 1600, 1500, 1400,  // 5:00 PM
    1300, 1200, 0, 0,      // 6:00 PM (Evening)//74
    0, 0, 0, 0,          // 7:00 PM
    0, 0, 0, 0,          // 8:00 PM
    0, 0, 0, 0,          // 9:00 PM
    0, 0, 0, 0,          // 10:00 PM
    0, 0, 0, 0           // 11:00 PM (Night)
];

// Initialize population
$population = initializePopulation($populationSize, $applianceCount, $solarPowerForecast);

// Genetic Algorithm Main Loop
for ($generation = 1; $generation <= $generationCount; $generation++) {
    $fitnessScores = calculateFitnessScores($population, $solarPowerForecast, $appliancePower);
    $newPopulation = [];

    for ($i = 0; $i < $populationSize; $i++) {
        $parent1 = selectParent($population, $fitnessScores);
        $parent2 = selectParent($population, $fitnessScores);
        $offspring = crossover($parent1, $parent2,$solarPowerForecast);
        $offspring=mutate($offspring,$applianceCount);
        $newPopulation[] = $offspring;
    }

    $population = $newPopulation;
}

$bestSchedule = findBestSchedule($population, $solarPowerForecast, $appliancePower);
printSchedule($bestSchedule, $appliancePower);

// Genetic Algorithm Functions

function initializePopulation($populationSize, $applianceCount, $solarPowerForecast) {
    $population = [];

    for ($i = 0; $i < $populationSize; $i++) {
        $schedule = generateRandomSchedule($applianceCount, $solarPowerForecast);
        $population[] = $schedule;
    }

    return $population;
}

function generateRandomSchedule($applianceCount, $solarPowerForecast) {
    global $appliancePower, $applianceDuration;
    $schedule = array_fill(0, count($solarPowerForecast), 0); // Initialize all appliances as off
    $timeSlots = count($solarPowerForecast);

    for ($slot = 0; $slot < $timeSlots; $slot++) {
        if (rand(0, 1) == 1  ) {//&& $solarPowerForecast[$slot]!=0
            // Randomly choose an appliance to turn on
            $applianceIndex = rand(1, $applianceCount);
            if($applianceDuration[$applianceIndex-1]!=0 && $appliancePower[$applianceIndex-1]<=$solarPowerForecast[$slot])
            {
                $schedule[$slot] = $applianceIndex; // Turn on the selected appliance
                $applianceDuration[$applianceIndex-1]=$applianceDuration[$applianceIndex-1]-15;
                echo  "applianceindex: ". $applianceIndex . " appliance duration: ". $applianceDuration[$applianceIndex-1] . "<br>";
            }
            
        }
    }
    return $schedule;
}

function calculateFitnessScores($population, $solarPowerForecast, $appliancePower) {
    $fitnessScores = [];

    foreach ($population as $schedule) {
        $fitnessScores[] = calculateFitness($schedule, $solarPowerForecast, $appliancePower);
    }

    return $fitnessScores;
}

function calculateFitness($schedule, $solarPowerForecast, $appliancePower) {
    global $totalAppliancePower,$totalAppliancePowerby15min ;
    $totalEnergyConsumption = 0;
    $totalSolarPower = array_sum($solarPowerForecast);
    $fitness=100;
    for ($slot = 0; $slot < count($schedule); $slot++) {
        if ($schedule[$slot]!=0) {
            $totalEnergyConsumption += $appliancePower[$schedule[$slot]-1]; 

        if($solarPowerForecast[$slot]==0 || $appliancePower[$schedule[$slot]-1]<$solarPowerForecast[$slot])
        {
            $fitness-=5;
        }
        
    }
    }
    
    
    if($totalEnergyConsumption==$totalAppliancePowerby15min)
    {
        if($fitness<99)
        {
            $fitness+=2;
        }
    }
    else if($totalEnergyConsumption>$totalAppliancePowerby15min){
        $fitness-=2;
    }
    else{
        $fitness-=3;
    }
    //return ($totalEnergyConsumption <= $totalSolarPower) ? 1 : 0;
//     if($totalEnergyConsumption==0)
//     {
//         return $fitness=1;
//     }
//     else{
//             if($totalSolarPower> $totalAppliancePower)
//     {
//         $fitness=($totalSolarPower-$totalAppliancePower)/$totalEnergyConsumption;
//         //echo "fitness in fitness function". $fitness;
//     }
//     else if($totalSolarPower<=$totalAppliancePower)
//     {
//         $fitness=1-($totalEnergyConsumption/$totalSolarPower);
//         echo "fitness in fitness function else if". $fitness;
//     }
//     return $fitness;
// }
return $fitness;
}

function selectParent($population, $fitnessScores) {
    $totalFitness = array_sum($fitnessScores);
    $randomValue = rand(0, $totalFitness);
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
    $crossoverPoint = rand(1, count($parent1) - 1);
    $offspring = array_merge(array_slice($parent1, 0, $crossoverPoint), array_slice($parent2, $crossoverPoint));

    return $offspring;
}
// function crossover($parent1, $parent2, $solarPowerForecast, $appliancePower) {
//     $crossoverPoint = rand(1, count($parent1) - 1);
//     $offspring = array_merge(array_slice($parent1, 0, $crossoverPoint), array_slice($parent2, $crossoverPoint));

//     // Ensure slots with 0 solar power and where appliance power exceeds solar power are set to 0
//     $timeSlots = count($solarPowerForecast);
    
//     for ($i = 0; $i < $timeSlots; $i++) {
//         if($offspring[$i]!=0){
//         if ($solarPowerForecast[$i] == 0 || $appliancePower[$offspring[$i] - 1] > $solarPowerForecast[$i]) {
//             $offspring[$i] = 0;
//         }
//     }
//     }
//     //printSchedule($offspring,$appliancePower);

//     return $offspring;
// }

function mutate(&$schedule, $applianceCount) {
    global $mutationRate;

    for ($i = 0; $i < count($schedule); $i++) {
        if (rand(0, 100) / 100 < $mutationRate) {
            $schedule[$i] = ($schedule[$i] == 0) ? rand(1, $applianceCount) : 0;
        }
    }
    return $schedule;
}
// function mutate(&$schedule, $applianceCount, $solarPowerForecast, $appliancePower) {
//     global $mutationRate;

//     $timeSlots = count($schedule);

//     for ($i = 0; $i < $timeSlots; $i++) {
//         if($schedule[$i]!=0){
//         // Check if the slot has sufficient solar power and appliance power doesn't exceed it
//         if ($solarPowerForecast[$i] > 0 && $appliancePower[$schedule[$i] - 1] <= $solarPowerForecast[$i]) {
//             if (rand(0, 100) / 100 < $mutationRate) {
//                 $schedule[$i] = ($schedule[$i] == 0) ? rand(1, $applianceCount) : 0; // Toggle appliance state
//             }
//         }
//     }
//     }
//     return $schedule;
// }


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
    echo " best fitness: " . $bestFitness . " ----------";
    return $bestSchedule;
}

function printSchedule($schedule, $appliancePower) {
    echo "Optimized Schedule: <br>";

    $timeSlots = 96;

    for ($slot = 0; $slot < $timeSlots; $slot++) {
        if($schedule[$slot]!=0){
        echo "Time Slot $slot: ";
                    echo "Appliance " . $schedule[$slot];
                }
            
        
        echo PHP_EOL;
    }
}


?>
