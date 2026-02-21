<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$company = App\Models\Company::create([
    'name' => 'Test BV',
    'email' => 'test@test.nl',
    'phone' => '0612345678',
]);

$lead = App\Models\Lead::create([
    'company_id' => $company->id,
    'name' => 'Jan Jansen',
    'email' => 'jan@test.nl',
]);

$quote = App\Models\Quote::create([
    'company_id' => $company->id,
    'lead_id' => $lead->id,
    'valid_until' => now()->addDays(30),
]);

echo "✅ Created test data:\n";
echo "   Company: {$company->name} (ID: {$company->id})\n";
echo "   Lead: {$lead->name} (ID: {$lead->id})\n";
echo "   Quote: {$quote->quote_number} (ID: {$quote->id})\n\n";
echo "🌐 Visit: https://sfh.ddev.site/admin/quotes/{$quote->id}/edit\n";
echo "👤 Login: admin@sfh.nl / password\n";
