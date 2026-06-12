<?php

namespace App\Http\Controllers;

use App\Models\ClassificationCondition;
use App\Models\ClassificationRule;
use App\Models\ProductStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ClassificationRuleController extends Controller
{
    // ── Shared dropdown data ─────────────────────────────────────────────────

    private function fieldKeys(): array
    {
        return config('product-classifier.allowed_field_keys');
    }

    private function operators(): array
    {
        return config('product-classifier.allowed_operators');
    }

    private function productStatuses(): array
    {
        $userId = Auth::id();

        return ProductStatus::active()
            ->forUser($userId)
            ->orderBy('priority')
            ->get(['id', 'name', 'slug', 'color'])
            ->map(fn ($s) => [
                'label' => $s->name,
                'value' => $s->id,
                'slug'  => $s->slug,
                'color' => $s->color,
            ])
            ->values()
            ->toArray();
    }

    // ── Validation rules ─────────────────────────────────────────────────────

    private function validationRules(): array
    {
        $fieldKeys = implode(',', $this->fieldKeys());
        $operators = implode(',', $this->operators());

        return [
            'name'                        => 'required|string|max:255',
            'description'                 => 'nullable|string',
            'product_status_id'           => 'required|exists:product_statuses,id',
            'match_type'                  => 'required|in:all,any',
            'priority'                    => 'required|integer|min:1',
            'is_active'                   => 'nullable|boolean',
            'conditions'                  => 'required|array|min:1',
            'conditions.*.field_key'      => "required|string|in:{$fieldKeys}",
            'conditions.*.operator'       => "required|string|in:{$operators}",
            'conditions.*.value'          => 'nullable|string',
            // 'conditions.*.value_type'     => 'nullable|string|max:50',
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // index
    // ─────────────────────────────────────────────────────────────────────────

    public function index()
    {
        $rules = ClassificationRule::with(['productStatus', 'conditions'])
            ->where('user_id', Auth::id())
            ->orderByDesc('priority')
            ->orderBy('name')
            ->get()
            ->map(fn ($rule) => [
                'id'              => $rule->id,
                'name'            => $rule->name,
                'description'     => $rule->description,
                'match_type'      => $rule->match_type,
                'priority'        => $rule->priority,
                'is_active'       => $rule->is_active,
                'conditions_count'=> $rule->conditions->count(),
                'product_status'  => $rule->productStatus ? [
                    'id'   => $rule->productStatus->id,
                    'name' => $rule->productStatus->name,
                    'slug' => $rule->productStatus->slug,
                    'color'=> $rule->productStatus->color,
                ] : null,
            ]);

        return Inertia::render('Rules/Index', [
            'rules' => $rules,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // create
    // ─────────────────────────────────────────────────────────────────────────

    public function create()
    {
        return Inertia::render('Rules/Create', [
            'product_statuses'    => $this->productStatuses(),
            'allowed_field_keys'  => $this->fieldKeys(),
            'allowed_operators'   => $this->operators(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // store
    // ─────────────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        logger()->info('Storing new classification rule', ['request_data' => $request->all()]);
        $data = $request->validate($this->validationRules());
        $userId = Auth::id();

            $rule = ClassificationRule::create([
                'user_id'           => $userId,
                'product_status_id' => $data['product_status_id'],
                'name'              => $data['name'],
                'description'       => $data['description'] ?? null,
                'match_type'        => $data['match_type'],
                'priority'          => $data['priority'],
                'is_active'         => $data['is_active'] ?? true,
            ]);

            foreach ($data['conditions'] as $index => $condition) {
                ClassificationCondition::create([
                    'user_id'                  => $userId,
                    'classification_rule_id'   => $rule->id,
                    'field_key'                => $condition['field_key'],
                    'operator'                 => $condition['operator'],
                    'value'                    => $condition['value'] ?? null,
                    'value_type'               => $condition['value_type'] ?? null,
                    'sort_order'               => $index,
                ]);
            }
        dd('Rule stored successfully.');
        return redirect()->route('rules.index', request()->only('shop', 'hmac', 'host', 'timestamp', 'locale', 'session'))
            ->with('success', 'Rule created successfully.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // edit
    // ─────────────────────────────────────────────────────────────────────────

    public function edit(ClassificationRule $rule)
    {
        abort_if($rule->user_id !== Auth::id(), 403);

        $rule->load(['productStatus', 'conditions']);

        return Inertia::render('Rules/Edit', [
            'rule'               => [
                'id'                => $rule->id,
                'name'              => $rule->name,
                'description'       => $rule->description,
                'product_status_id' => $rule->product_status_id,
                'match_type'        => $rule->match_type,
                'priority'          => $rule->priority,
                'is_active'         => $rule->is_active,
                'conditions'        => $rule->conditions->map(fn ($c) => [
                    'field_key'  => $c->field_key,
                    'operator'   => $c->operator,
                    'value'      => $c->value,
                    'value_type' => $c->value_type,
                ])->values(),
            ],
            'product_statuses'   => $this->productStatuses(),
            'allowed_field_keys' => $this->fieldKeys(),
            'allowed_operators'  => $this->operators(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // update
    // ─────────────────────────────────────────────────────────────────────────

    public function update(Request $request, ClassificationRule $rule)
    {
        abort_if($rule->user_id !== Auth::id(), 403);

        $data = $request->validate($this->validationRules());

        DB::transaction(function () use ($rule, $data) {
            $userId = Auth::id();

            $rule->update([
                'product_status_id' => $data['product_status_id'],
                'name'              => $data['name'],
                'description'       => $data['description'] ?? null,
                'match_type'        => $data['match_type'],
                'priority'          => $data['priority'],
                'is_active'         => $data['is_active'] ?? true,
            ]);

            // Replace conditions: delete old, insert new
            $rule->conditions()->delete();

            foreach ($data['conditions'] as $index => $condition) {
                ClassificationCondition::create([
                    'user_id'                  => $userId,
                    'classification_rule_id'   => $rule->id,
                    'field_key'                => $condition['field_key'],
                    'operator'                 => $condition['operator'],
                    'value'                    => $condition['value'] ?? null,
                    'value_type'               => $condition['value_type'] ?? null,
                    'sort_order'               => $index,
                ]);
            }
        });

        return redirect()->route('rules.index', request()->only('shop', 'hmac', 'host', 'timestamp', 'locale', 'session'))
            ->with('success', 'Rule updated successfully.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // destroy
    // ─────────────────────────────────────────────────────────────────────────

    public function destroy(ClassificationRule $rule)
    {
        abort_if($rule->user_id !== Auth::id(), 403);

        DB::transaction(function () use ($rule) {
            $rule->conditions()->delete();
            $rule->delete();
        });

        return back()->with('success', 'Rule deleted successfully.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // toggleStatus
    // ─────────────────────────────────────────────────────────────────────────

    public function toggleStatus(ClassificationRule $rule)
    {
        abort_if($rule->user_id !== Auth::id(), 403);

        $rule->update(['is_active' => ! $rule->is_active]);

        return back()->with('success', $rule->is_active ? 'Rule enabled.' : 'Rule disabled.');
    }
}
