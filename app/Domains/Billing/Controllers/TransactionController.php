<?php

namespace App\Domains\Billing\Controllers;

use App\Domains\Billing\Models\Transaction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'global' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in([Transaction::STATUS_PENDING, Transaction::STATUS_PAID, Transaction::STATUS_FAILED, Transaction::STATUS_REFUNDED, Transaction::STATUS_CANCELLED])],
            'type' => ['nullable', Rule::in([Transaction::TYPE_CHARGE, Transaction::TYPE_REFUND])],
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $query = Transaction::query()->with(['customer:id,name,last_name,email', 'invoice.subscription.campaign:id,user_id,name', 'invoice.subscription.plan:id,name']);
        if ($search = $validated['global'] ?? null) {
            $query->where(fn ($query) => $query->where('external_id', 'like', "%{$search}%")->orWhere('provider', 'like', "%{$search}%")->orWhereHas('customer', fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")));
        }
        foreach (['status', 'type'] as $field) {
            if ($value = $validated[$field] ?? null) {
                $query->where($field, $value);
            }
        }
        $items = $query->latest()->paginate((int) ($validated['perPage'] ?? 7));

        return response()->json(['data' => $items->items(), 'pagination' => ['current_page' => $items->currentPage(), 'last_page' => $items->lastPage(), 'per_page' => $items->perPage(), 'total' => $items->total()]]);
    }
}
