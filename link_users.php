<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$employees = App\Models\Hr\Employee::whereNull('user_id')->get();
$count = 0;
foreach($employees as $emp) {
    $user = App\Models\User::where('email', $emp->email)->first();
    if($user) {
        $emp->user_id = $user->id;
        $emp->save();
        echo "Linked {$emp->email} to User {$user->id}\n";
        $count++;
    }
}
echo "Total {$count} employees linked.\n";
