<?php
$q = \App\Models\Quote::find(29);
echo "moneybird_quote_id: " . ($q->moneybird_quote_id ?? 'NULL') . PHP_EOL;
echo "status: " . $q->status->value . PHP_EOL;
echo "Knop zichtbaar (verstuur): " . (! $q->moneybird_quote_id ? 'JA' : 'NEE - al verstuurd') . PHP_EOL;
echo "Knop zichtbaar (bijwerken): " . ($q->moneybird_quote_id ? 'JA' : 'NEE') . PHP_EOL;
