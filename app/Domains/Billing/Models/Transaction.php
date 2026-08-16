<?php

namespace App\Domains\Billing\Models;

use App\Domains\User\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['invoice_id', 'user_id', 'external_id', 'provider', 'payment_method', 'type', 'status', 'amount', 'processed_at', 'metadata'])]
class Transaction extends Model
{
    public const PAYMENT_PIX = 'pix';

    public const PAYMENT_CREDIT_CARD = 'credit_card';

    public const PAYMENT_DEBIT_CARD = 'debit_card';

    public const PAYMENT_BANK_SLIP = 'bank_slip';

    public const PAYMENT_BANK_TRANSFER = 'bank_transfer';

    public const PAYMENT_CASH = 'cash';

    public const TYPE_CHARGE = 'charge';

    public const TYPE_REFUND = 'refund';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_FAILED = 'failed';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUS_CANCELLED = 'cancelled';

    public static function paymentMethods(): array
    {
        return [
            self::PAYMENT_PIX,
            self::PAYMENT_CREDIT_CARD,
            self::PAYMENT_DEBIT_CARD,
            self::PAYMENT_BANK_SLIP,
            self::PAYMENT_BANK_TRANSFER,
            self::PAYMENT_CASH,
        ];
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'processed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
