<?php
// Test met een tijdelijke company
$q = \App\Models\Quote::with('company')->find(29);
$q->company->contact_person = 'Kevin';
$mb = app(\App\Services\MoneybirdService::class);
echo '--- pre_text MET voornaam ---' . PHP_EOL;
echo $mb->buildPreText($q) . PHP_EOL;
echo PHP_EOL;
$q->company->contact_person = null;
echo '--- pre_text ZONDER voornaam ---' . PHP_EOL;
echo $mb->buildPreText($q) . PHP_EOL;
