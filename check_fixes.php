<?php
$q = \App\Models\Quote::with('company', 'items')->find(29);

// Test aanhef met en zonder voornaam
echo '--- Met voornaam ---' . PHP_EOL;
$q->company->contact_person = 'Kevin';
$mb = app(\App\Services\MoneybirdService::class);
echo $mb->buildPreText($q) . PHP_EOL . PHP_EOL;

echo '--- Zonder voornaam (fallback bedrijfsnaam) ---' . PHP_EOL;
$q->company->contact_person = null;
echo $mb->buildPreText($q) . PHP_EOL . PHP_EOL;

// Test pile_length in description
echo '--- itemDescription ---' . PHP_EOL;
$item = $q->items->first();
if ($item) {
    $item->pile_length = 3.5;
    $ref = new ReflectionClass($mb);
    $method = $ref->getMethod('itemDescription');
    $method->setAccessible(true);
    echo $method->invoke($mb, $item) . PHP_EOL;
    $item->pile_length = null;
    echo $method->invoke($mb, $item) . ' (geen lengte)' . PHP_EOL;
} else {
    echo 'Geen items op quote 29' . PHP_EOL;
}
