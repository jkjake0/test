<?php

namespace App\Models;

use App\Traits\DefaultDatetimeFormat;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledRepayment extends Model
{
    use HasFactory, DefaultDatetimeFormat;

    public const STATUS_DUE = 'due';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_REPAID = 'repaid';

    public const DATE_FORMAT = 'Y-m-d';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'scheduled_repayments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'loan_id',
        'amount',
        'outstanding_amount',
        'currency_code',
        'due_date',
        'status',
    ];

    protected $casts = [
        'due_date' => 'datetime:Y-m-d'
    ];

    protected $dates = [
        'due_date',
    ];

    /**
     * Mutator processed_at
     * @param $value
     */
    public function setDueDateAttribute($value) {
        $this->attributes['due_date'] = $value instanceof Carbon
            ? $value->format(ScheduledRepayment::DATE_FORMAT)
            : Carbon::createFromFormat(ScheduledRepayment::DATE_FORMAT, $value)->format(ScheduledRepayment::DATE_FORMAT);
    }

    /**
     * Accessor processed_at
     */
    public function getDueDateAttribute() {
        $this->attributes['due_date'] = $this->attributes['due_date'] instanceof Carbon
            ? $this->attributes['due_date']->format(ScheduledRepayment::DATE_FORMAT)
            : Carbon::createFromFormat(ScheduledRepayment::DATE_FORMAT, $this->attributes['due_date'])->format(ScheduledRepayment::DATE_FORMAT);
    }

    /**
     * A Scheduled Repayment belongs to a Loan
     *
     * @return BelongsTo
     */
    public function loan()
    {
        return $this->belongsTo(Loan::class, 'loan_id');
    }
}
