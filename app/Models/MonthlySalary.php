<?php

namespace App\Models;

use App\Enums\SalaryStatus;
use Database\Factories\MonthlySalaryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlySalary extends Model
{
    /** @use HasFactory<MonthlySalaryFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'salary_month',
        'salary_amount',
        'given_status',
        'has_bonus',
        'bonus_amount',
        'paid_at',
        'paid_by',
        'version',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'salary_month' => 'date',
            'salary_amount' => 'decimal:2',
            'given_status' => SalaryStatus::class,
            'has_bonus' => 'boolean',
            'bonus_amount' => 'decimal:2',
            'paid_at' => 'immutable_datetime',
            'version' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function isPaid(): bool
    {
        return $this->given_status === SalaryStatus::Paid;
    }

    public function isUnpaid(): bool
    {
        return $this->given_status === SalaryStatus::Unpaid;
    }

    public function totalAmount(): string
    {
        return bcadd((string) $this->salary_amount, (string) $this->bonus_amount, 2);
    }
}
