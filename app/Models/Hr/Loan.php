<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Loan extends Model
{
    use HasFactory;

    protected $table = 'hr_loans';

    protected $fillable = [
        'employee_id',
        'loan_type',          // salary_deduction | self_paid
        'amount',
        'installment_amount',
        'total_installments',
        'installments_paid',
        'start_month',        // YYYY-MM
        'expected_end_month', // YYYY-MM
        'status',             // pending, approved, rejected, paid
        'reason',
        'notes',
        'paid_amount',
        'disbursed_at',
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'disbursed_at' => 'date',
        'approved_at'  => 'date',
    ];

    // ──────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function payments()
    {
        return $this->hasMany(LoanPayment::class, 'loan_id')->orderBy('payment_date', 'asc');
    }

    public function scheduledDeductions()
    {
        return $this->hasMany(LoanScheduledDeduction::class, 'loan_id');
    }

    // ──────────────────────────────────────────
    // Accessors
    // ──────────────────────────────────────────

    public function getRemainingAmountAttribute(): float
    {
        return max(0, $this->amount - $this->paid_amount);
    }

    public function getMonthlyInstallmentAttribute(): float
    {
        if ($this->loan_type === 'salary_deduction') {
            if ($this->total_installments > 0) {
                return round($this->amount / $this->total_installments, 2);
            }
            return (float) ($this->installment_amount ?? 0);
        }
        return 0;
    }

    public function getRemainingInstallmentsAttribute(): int
    {
        return max(0, ($this->total_installments ?? 0) - ($this->installments_paid ?? 0));
    }

    public function getProgressPercentageAttribute(): float
    {
        if ($this->amount <= 0) return 0;
        return min(100, round(($this->paid_amount / $this->amount) * 100, 1));
    }

    public function getIsOverdueAttribute(): bool
    {
        if (!$this->expected_end_month || $this->status === 'paid') return false;
        return Carbon::parse($this->expected_end_month . '-01')->endOfMonth()->isPast();
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->loan_type === 'salary_deduction' ? 'Salary Deduction' : 'Self-Paid';
    }

    // ──────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'approved')->whereRaw('paid_amount < amount');
    }

    public function scopeSalaryDeduction($query)
    {
        return $query->where('loan_type', 'salary_deduction');
    }

    public function scopeSelfPaid($query)
    {
        return $query->where('loan_type', 'self_paid');
    }

    // ──────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────

    /**
     * Calculate installment plan based on amount and number of months.
     */
    public static function calculatePlan(float $amount, int $months): array
    {
        if ($months <= 0) return [];
        $monthly = round($amount / $months, 2);
        // Adjust last installment for rounding diff
        $lastInstallment = $amount - ($monthly * ($months - 1));
        $startDate = Carbon::now()->addMonth()->startOfMonth();
        $schedule  = [];
        for ($i = 1; $i <= $months; $i++) {
            $schedule[] = [
                'month'  => $startDate->format('Y-m'),
                'label'  => $startDate->format('F Y'),
                'amount' => ($i === $months) ? round($lastInstallment, 2) : $monthly,
            ];
            $startDate->addMonth();
        }
        return [
            'monthly_installment' => $monthly,
            'total_installments'  => $months,
            'total_amount'        => $amount,
            'end_month'           => Carbon::now()->addMonths($months)->format('Y-m'),
            'end_month_label'     => Carbon::now()->addMonths($months)->format('F Y'),
            'schedule'            => $schedule,
        ];
    }
}
