<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithDataTable;
use App\Models\ApprovalRequest;
use App\Models\Site;
use App\Services\ApprovalWorkflowService;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SitesController extends Controller
{
    use InteractsWithDataTable;

    /**
     * Display a listing of sites.
     */
    public function index(Request $request): View
    {
        $query = Site::query()->with('parent:id,name')->withCount('employees');

        $this->applyTableSearch($query, $this->tableSearch($request), [
            'code',
            'name',
            'description',
            'address',
            'city',
            'province',
            'phone',
            'email',
        ]);

        $this->applyTableFilters($query, $request);

        [$sort, $direction] = $this->applyTableSort($query, [
            'code' => 'code',
            'name' => 'name',
            'type' => 'type',
            'parent' => 'parent_id',
            'city' => 'city',
            'province' => 'province',
            'employees' => 'employees_count',
            'is_active' => 'is_active',
        ], $request->query('sort'), $request->query('direction'), 'name');

        $sites = $query->paginate($this->tablePerPage($request))->withQueryString();

        return view('sites.index', [
            'sites' => $sites,
            'sort' => $sort,
            'direction' => $direction,
            'parents' => Site::query()->active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Display the site details.
     */
    public function show(Site $site): View
    {
        $site->load(['parent:id,name', 'children:id,parent_id,code,name,type,is_active']);

        return view('sites.show', [
            'site' => $site,
            'trail' => $site->ancestorTrail(),
            'employees' => $site->employees()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'employee_number', 'position_id']),
        ]);
    }

    /**
     * Show the form for creating a site.
     */
    public function create(): View
    {
        return view('sites.create', ['parentOptions' => Site::parentOptions()]);
    }

    /**
     * Store a newly created site.
     */
    public function store(Request $request, ApprovalWorkflowService $workflow): RedirectResponse
    {
        $data = $this->validated($request);

        if ($workflow->isApprovalActive('sites')) {
            if (blank($data['code'] ?? null)) {
                unset($data['code']);
            }

            $workflow->submit($request->user(), 'sites', ApprovalRequest::ACTION_CREATE, null, $data);

            return redirect()->route('sites.index')->with('status', __('Site change request submitted for approval.'));
        }

        DB::transaction(function () use (&$data): void {
            if (blank($data['code'] ?? null)) {
                $data['code'] = Site::generateCode();
            }

            Site::create($data);
        });

        return redirect()->route('sites.index')->with('status', __('Site created.'));
    }

    /**
     * Show the form for editing a site.
     */
    public function edit(Site $site): View
    {
        return view('sites.edit', [
            'site' => $site,
            'parentOptions' => Site::parentOptions($site),
        ]);
    }

    /**
     * Update the specified site.
     */
    public function update(Request $request, Site $site, ApprovalWorkflowService $workflow): RedirectResponse
    {
        $data = $this->validated($request, $site);

        if ($workflow->isApprovalActive('sites')) {
            if (blank($data['code'] ?? null)) {
                $data['code'] = $site->code;
            }

            $workflow->submit($request->user(), 'sites', ApprovalRequest::ACTION_UPDATE, $site->id, $data);

            return redirect()->route('sites.index')->with('status', __('Site change request submitted for approval.'));
        }

        if (blank($data['code'] ?? null)) {
            unset($data['code']);
        }

        $site->update($data);

        return redirect()->route('sites.index')->with('status', __('Site updated.'));
    }

    /**
     * Soft delete the specified site; sites with children or employees
     * cannot be deleted.
     */
    public function destroy(Site $site, ApprovalWorkflowService $workflow): RedirectResponse
    {
        if ($site->children()->exists()) {
            return back()->withErrors(['site' => __('This site has child sites and cannot be deleted.')]);
        }

        if ($site->employees()->exists()) {
            return back()->withErrors(['site' => __('This site is assigned to employees and cannot be deleted.')]);
        }

        if ($workflow->isApprovalActive('sites')) {
            $workflow->submit(request()->user(), 'sites', ApprovalRequest::ACTION_DELETE, $site->id, []);

            return redirect()->route('sites.index')->with('status', __('Site change request submitted for approval.'));
        }

        $site->delete();

        return redirect()->route('sites.index')->with('status', __('Site deleted.'));
    }

    /**
     * Apply the type, parent and status filters from the query string.
     *
     * The list UI allows a single active filter; only the first matching
     * parameter is applied so stale URLs cannot combine filters.
     */
    protected function applyTableFilters(Builder $query, Request $request): void
    {
        if (in_array($request->query('type'), Site::TYPES, true)) {
            $query->where('sites.type', $request->query('type'));

            return;
        }

        if (is_numeric($request->query('parent_id'))) {
            $query->where('sites.parent_id', (int) $request->query('parent_id'));

            return;
        }

        $this->applyTableStatusFilter($query, $request);
    }

    /**
     * Validate and normalize the site payload.
     *
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?Site $site = null): array
    {
        $data = $request->validate([
            'parent_id' => $this->parentRules($request, $site),
            'code' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('sites', 'code')->ignore($site?->id)],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(Site::TYPES)],
            'description' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    /**
     * The submitted parent must exist, must not sit inside the target's
     * own subtree, and must be a category allowed to hold the submitted
     * type.
     *
     * @return list<mixed>
     */
    protected function parentRules(Request $request, ?Site $site): array
    {
        return [
            'nullable',
            Rule::exists('sites', 'id'),
            Site::parentRule($site),
            function (string $attribute, mixed $value, callable $fail) use ($request): void {
                $message = Site::parentTypeErrorMessage($request->input('type'), $value);

                if ($message !== null) {
                    $fail($message);
                }
            },
        ];
    }
}
