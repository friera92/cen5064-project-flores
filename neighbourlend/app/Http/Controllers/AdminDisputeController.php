<?php

namespace App\Http\Controllers;

use App\Domain\Models\Reservation;
use App\Domain\States\ReservationState;
use App\Domain\States\ToolReturnedCondition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminDisputeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'condition' => ['nullable', Rule::enum(ToolReturnedCondition::class)],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('from') ? ['after_or_equal:from'] : [])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = Reservation::query()
            ->where('status', ReservationState::DISPUTED->value)
            ->with([
                'borrower:id,name',
                'tool' => fn ($tool) => $tool->withTrashed()->select('id', 'owner_id', 'title', 'picture'),
                'tool.owner' => fn ($owner) => $owner->withTrashed()->select('id', 'name'),
            ]);

        if (!empty($filters['condition'])) {
            $query->where('returned_condition', $filters['condition']);
        }
        if (!empty($filters['from'])) {
            $query->whereDate('start_date', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->whereDate('end_date', '<=', $filters['to']);
        }
        $search = trim($filters['q'] ?? '');
        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                if (ctype_digit($search)) {
                    $builder->orWhere('reservations.id', (int) $search);
                }
                $builder->orWhereHas('borrower', fn ($user) => $user->withTrashed()->where('name', 'ilike', "%{$search}%"))
                    ->orWhereHas('tool', function ($tool) use ($search) {
                        $tool->withTrashed()->where(function ($query) use ($search) {
                            $query->where('title', 'ilike', "%{$search}%")
                                ->orWhereHas('owner', fn ($owner) => $owner->withTrashed()->where('name', 'ilike', "%{$search}%"));
                        });
                    });
            });
        }

        return response()->json($query->orderByDesc('created_at')->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 15));
    }
}
