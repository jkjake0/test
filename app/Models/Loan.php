<?php

namespace App\Models;

use App\Traits\DefaultDatetimeFormat;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Loan extends Model
{
    use DefaultDatetimeFormat;

    public const STATUS_DUE = 'due';
    public const STATUS_REPAID = 'repaid';

    public const CURRENCY_SGD = 'SGD';
    public const CURRENCY_VND = 'VND';

    public const DATE_FORMAT = 'Y-m-d';

    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'loans';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'amount',
        'terms',
        'outstanding_amount',
        'currency_code',
        'processed_at',
        'status',
    ];

    protected $casts = [
        'processed_at' => 'datetime:Y-m-d'
    ];

    protected $dates = [
        'processed_at',
    ];

    /**
     * Mutator processed_at
     * @param $value
     */
    public function setProcessedAtAttribute($value) {
        $this->attributes['processed_at'] = $value instanceof Carbon
            ? $value->format(Loan::DATE_FORMAT)
            : Carbon::createFromFormat(Loan::DATE_FORMAT, $value)->format(Loan::DATE_FORMAT);
    }

    /**
     * Accessor processed_at
     */
    public function getProcessedAtAttribute() {
        $this->attributes['processed_at'] = $this->attributes['processed_at'] instanceof Carbon
            ? $this->attributes['processed_at']->format(Loan::DATE_FORMAT)
            : Carbon::createFromFormat(Loan::DATE_FORMAT, $this->attributes['processed_at'])->format(Loan::DATE_FORMAT);
    }

    /**
     * A Loan belongs to a User
     *
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * A Loan has many Scheduled Repayments
     *
     * @return HasMany
     */
    public function scheduledRepayments()
    {
        return $this->hasMany(ScheduledRepayment::class, 'loan_id');
    }

    /**
     * A Loan has many Scheduled Repayments
     *
     * @return HasMany
     */
    public function receivedRepayments(): HasMany
    {
        return $this->hasMany(ReceivedRepayment::class, 'loan_id');
    }
}
