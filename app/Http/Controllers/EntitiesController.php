<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithDataTable;
use App\Models\ApprovalRequest;
use App\Models\Entity;
use App\Services\ApprovalWorkflowService;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EntitiesController extends Controller
{
    use InteractsWithDataTable;

    /**
     * Display a listing of entities.
     */
    public function index(Request $request): View
    {
        $query = Entity::query();

        $this->applyTableSearch($query, $this->tableSearch($request), [
            'code',
            'name',
            'email',
            'phone',
            'npwp',
            'identity_number',
        ]);

        $this->applyTableFilters($query, $request);

        [$sort, $direction] = $this->applyTableSort($query, [
            'code' => 'code',
            'name' => 'name',
            'type' => 'type',
            'role' => 'role',
            'is_active' => 'is_active',
            'created_at' => 'created_at',
        ], $request->query('sort'), $request->query('direction'), 'name');

        $entities = $query->paginate($this->tablePerPage($request))->withQueryString();

        return view('entities.index', [
            'entities' => $entities,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    /**
     * Display the entity details.
     */
    public function show(Entity $entity): View
    {
        return view('entities.show', ['entity' => $entity]);
    }

    /**
     * Show the form for creating an entity.
     */
    public function create(): View
    {
        return view('entities.create');
    }

    /**
     * Store a newly created entity.
     */
    public function store(Request $request, ApprovalWorkflowService $workflow): RedirectResponse
    {
        $data = $this->validated($request);

        if ($workflow->isApprovalActive('entities')) {
            if (blank($data['code'] ?? null)) {
                unset($data['code']);
            }

            $workflow->submit($request->user(), 'entities', ApprovalRequest::ACTION_CREATE, null, $data);

            return redirect()->route('entities.index')->with('status', __('Entity change request submitted for approval.'));
        }

        DB::transaction(function () use (&$data): void {
            if (blank($data['code'] ?? null)) {
                $data['code'] = Entity::generateCode();
            }

            Entity::create($data);
        });

        return redirect()->route('entities.index')->with('status', __('Entity created.'));
    }

    /**
     * Show the form for editing an entity.
     */
    public function edit(Entity $entity): View
    {
        return view('entities.edit', ['entity' => $entity]);
    }

    /**
     * Update the specified entity.
     */
    public function update(Request $request, Entity $entity, ApprovalWorkflowService $workflow): RedirectResponse
    {
        $data = $this->validated($request, $entity);

        if ($workflow->isApprovalActive('entities')) {
            if (blank($data['code'] ?? null)) {
                $data['code'] = $entity->code;
            }

            $workflow->submit($request->user(), 'entities', ApprovalRequest::ACTION_UPDATE, $entity->id, $data);

            return redirect()->route('entities.index')->with('status', __('Entity change request submitted for approval.'));
        }

        if (blank($data['code'] ?? null)) {
            unset($data['code']);
        }

        $entity->update($data);

        return redirect()->route('entities.index')->with('status', __('Entity updated.'));
    }

    /**
     * Soft delete the specified entity.
     */
    public function destroy(Entity $entity, ApprovalWorkflowService $workflow): RedirectResponse
    {
        if ($workflow->isApprovalActive('entities')) {
            $workflow->submit(request()->user(), 'entities', ApprovalRequest::ACTION_DELETE, $entity->id, []);

            return redirect()->route('entities.index')->with('status', __('Entity change request submitted for approval.'));
        }

        $entity->delete();

        return redirect()->route('entities.index')->with('status', __('Entity deleted.'));
    }

    /**
     * Apply the role, type and status filters from the query string.
     *
     * The list UI allows a single active filter; only the first matching
     * parameter is applied so stale URLs cannot combine filters.
     */
    protected function applyTableFilters(Builder $query, Request $request): void
    {
        if (in_array($request->query('role'), Entity::ROLES, true)) {
            $query->where('entities.role', $request->query('role'));

            return;
        }

        if (in_array($request->query('type'), Entity::TYPES, true)) {
            $query->where('entities.type', $request->query('type'));

            return;
        }

        if (in_array($request->query('status'), ['active', 'inactive'], true)) {
            $query->where('entities.is_active', $request->query('status') === 'active');
        }
    }

    /**
     * Validate and normalize the entity payload.
     *
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?Entity $entity = null): array
    {
        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('entities', 'code')->ignore($entity?->id)],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(Entity::TYPES)],
            'role' => ['required', Rule::in(Entity::ROLES)],
            'npwp' => ['nullable', 'string', 'max:50'],
            'identity_number' => ['nullable', 'string', 'max:50', Rule::unique('entities', 'identity_number')->ignore($entity?->id)],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
