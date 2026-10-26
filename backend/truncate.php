<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

DB::table('employee_assignments')->delete();
DB::table('employees')->delete();
DB::table('positions')->delete();
DB::table('divisions')->delete();
DB::table('departments')->delete();

// We will also delete companies and branches that are not the standard 3.
// Standard companies: OPMC, Jangeby Office, Peby Motor, Jangeby Central
DB::table('branches')->whereNotIn('name', ['OPMC', 'Jangeby Office', 'Peby Motor 1', 'Peby Motor 2', 'Peby Motor 3', 'Peby Motor 4', 'Peby Motor 5'])->delete();
DB::table('companies')->whereNotIn('name', ['OPMC', 'Jangeby Office', 'Peby Motor', 'Jangeby Central'])->delete();

echo "Deleted successfully.\n";
