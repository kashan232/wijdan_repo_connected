<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanPayment extends Model
{
    use HasFactory;

    protected $table = 'hr_loan_payments';

    protected $fillable = [
        'loan_id',
        'amount',
        'payment_date',
        'type',       // salary_deduction, bank_transfer, cash
        'source',     // manual, payroll_auto
        'payroll_id',
        'reference',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class, 'loan_id');
    }

    public function payroll()
    {
        return $this->belongsTo(Payroll::class, 'payroll_id');
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'salary_deduction' => 'Salary Deduction',
            'bank_transfer'    => 'Bank Transfer',
            'cash'             => 'Cash',
            default            => ucfirst($this->type),
        };
    }
}
