<?php
$content = file_get_contents('app/Http/Controllers/ReportingController.php');

$replacement = <<<PHP
\$isSuperAdmin = auth()->check() && auth()->user()->hasRole('Super Admin');
            
            if (\$start && \$end) {
                \$startLimit = \$start . ' 00:00:00';
                if (!\$isSuperAdmin && \$startLimit < '2026-10-01 00:00:00') {
                    \$startLimit = '2026-10-01 00:00:00';
                }
                \$query->whereBetween('sales.created_at', [\$startLimit, \$end . ' 23:59:59']);
            } else {
                if (!\$isSuperAdmin) {
                    \$query->where('sales.created_at', '>=', '2026-10-01 00:00:00');
                }
            }
PHP;

// Using regex for the exact block:
$pattern = "/if\s*\(\\$start\s*&&\s*\\$end\)\s*\{\s*(?:\/\/[^\n]*\n\s*)*\\$query->whereBetween\('sales\.created_at',\s*\[\\$start\s*\.\s*' 00:00:00',\s*\\$end\s*\.\s*' 23:59:59'\]\);\s*\}/s";
$content = preg_replace($pattern, $replacement, $content);

$expense_replacement = <<<PHP
\$isSuperAdmin = auth()->check() && auth()->user()->hasRole('Super Admin');
        if (\$request->filled('start_date') && \$request->filled('end_date')) {
            \$startDate = \$request->start_date;
            if (!\$isSuperAdmin && \$startDate < '2026-10-01') {
                \$startDate = '2026-10-01';
            }
            \$query->whereBetween('date', [
                \$startDate,
                \$request->end_date,
            ]);
        } else {
            if (!\$isSuperAdmin) {
                \$query->where('date', '>=', '2026-10-01');
            }
        }
PHP;

$pattern2 = "/if\s*\(\\$request->filled\('start_date'\)\s*&&\s*\\$request->filled\('end_date'\)\)\s*\{\s*\\$query->whereBetween\('date',\s*\[\s*\\$request->start_date,\s*\\$request->end_date,?\s*\]\);\s*\}/s";
$content = preg_replace($pattern2, $expense_replacement, $content);

file_put_contents('app/Http/Controllers/ReportingController.php', $content);
echo "ReportingController updated\n";


// Now for HomeController System_Reports
$homeContent = file_get_contents('app/Http/Controllers/HomeController.php');

$home_replacement = <<<PHP
\$startDate = \$request->start_date;
        \$endDate = \$request->end_date;

        \$isSuperAdmin = \\Illuminate\\Support\\Facades\\Auth::check() && \\Illuminate\\Support\\Facades\\Auth::user()->hasRole('Super Admin');
        if (!\$isSuperAdmin) {
            if (\$startDate && \$startDate < '2026-10-01') {
                \$startDate = '2026-10-01';
            }
            if (!\$startDate) {
                // By default System Reports might show all, so restrict to 2026-10-01 if no date
                \$startDate = '2026-10-01'; 
                // Also default end date if empty
                if (!\$endDate) {
                    \$endDate = date('Y-m-d');
                }
            }
            if (\$endDate && \$endDate < '2026-10-01') {
                // If they ask for totally hidden period, give them nothing
                \$endDate = '2026-09-30';
                \$startDate = '2026-10-01'; // this will yield 0
            }
        }
PHP;

// Find:
// $startDate = $request->start_date;
// $endDate = $request->end_date;
$pattern3 = "/\\$startDate\s*=\s*\\$request->start_date;\s*\\$endDate\s*=\s*\\$request->end_date;/s";
$homeContent = preg_replace($pattern3, $home_replacement, $homeContent);

file_put_contents('app/Http/Controllers/HomeController.php', $homeContent);
echo "HomeController updated\n";

