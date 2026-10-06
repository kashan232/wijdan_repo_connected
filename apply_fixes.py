import re

def fix_reporting_controller():
    with open('app/Http/Controllers/ReportingController.php', 'r', encoding='utf-8') as f:
        content = f.read()

    replacement = """
            $isSuperAdmin = auth()->check() && auth()->user()->hasRole('Super Admin');
            
            if ($start && $end) {
                $startLimit = $start . ' 00:00:00';
                if (!$isSuperAdmin && $startLimit < '2026-10-01 00:00:00') {
                    $startLimit = '2026-10-01 00:00:00';
                }
                $query->whereBetween('sales.created_at', [$startLimit, $end . ' 23:59:59']);
            } else {
                if (!$isSuperAdmin) {
                    $query->where('sales.created_at', '>=', '2026-10-01 00:00:00');
                }
            }
"""
    
    content = re.sub(
        r"if\s*\(\$start\s*&&\s*\$end\)\s*\{\s*(?://[^\n]*\n\s*)*\$query->whereBetween\('sales\.created_at',\s*\[\$start\s*\.\s*' 00:00:00',\s*\$end\s*\.\s*' 23:59:59'\]\);\s*\}",
        replacement.strip(),
        content
    )

    expense_replacement = """
        $isSuperAdmin = auth()->check() && auth()->user()->hasRole('Super Admin');
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = $request->start_date;
            if (!$isSuperAdmin && $startDate < '2026-10-01') {
                $startDate = '2026-10-01';
            }
            $query->whereBetween('date', [
                $startDate,
                $request->end_date,
            ]);
        } else {
            if (!$isSuperAdmin) {
                $query->where('date', '>=', '2026-10-01');
            }
        }
"""
    content = re.sub(
        r"if\s*\(\$request->filled\('start_date'\)\s*&&\s*\$request->filled\('end_date'\)\)\s*\{\s*\$query->whereBetween\('date',\s*\[\s*\$request->start_date,\s*\$request->end_date,?\s*\]\);\s*\}",
        expense_replacement.strip(),
        content
    )

    with open('app/Http/Controllers/ReportingController.php', 'w', encoding='utf-8') as f:
        f.write(content)

fix_reporting_controller()
