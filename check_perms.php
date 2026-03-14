<?php
require base_path('check_perms.php');
echo PHP_EOL . "Registered Filament resources:" . PHP_EOL;
foreach (\Filament\Facades\Filament::getResources() as $res) {
    echo "  " . $res . PHP_EOL;
}
