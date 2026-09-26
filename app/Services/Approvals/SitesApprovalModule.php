<?php

namespace App\Services\Approvals;

use App\Models\ApprovalRequest;
use App\Models\Site;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SitesApprovalModule extends CrudApprovalModule
{
    public function key(): string
    {
        return 'sites';
    }

    public function label(): string
    {
        return __('Sites');
    }

    public function targetLabel(): string
    {
        return __('Site');
    }

    protected function modelClass(): string
    {
        return Site::class;
    }

    protected function fields(): array
    {
        return [
            'parent_id',
            'code',
            'name',
            'type',
            'description',
            'address',
            'city',
            'province',
            'postal_code',
            'phone',
            'email',
            'notes',
            'is_active',
        ];
    }

    protected function rules(?Model $target, string $action): array
    {
        return [
            'parent_id' => [
                'nullable',
                Rule::exists('sites', 'id'),
                Site::parentRule($target instanceof Site ? $target : null),
            ],
            'code' => ['nullable', Rule::unique('sites', 'code')->ignore($target?->getKey())],
            'name' => ['required'],
            'type' => ['required', Rule::in(Site::TYPES)],
        ];
    }

    /**
     * Tree integrity is a cross-field rule, so it runs against the whole
     * payload rather than through rules(): the submitted parent must be
     * able to hold a child of the submitted type.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function assertApplicable(?ApprovalRequest $request, ?Model $target, string $action, array $payload): void
    {
        $message = Site::parentTypeErrorMessage(
            isset($payload['type']) ? (string) $payload['type'] : null,
            $payload['parent_id'] ?? null,
        );

        if ($message !== null) {
            throw ValidationException::withMessages(['parent_id' => $message]);
        }
    }

    /**
     * Sites holding child sites or employees are never deletable.
     */
    protected function assertDeletable(Model $target): void
    {
        if (! $target instanceof Site) {
            return;
        }

        if ($target->children()->exists()) {
            throw ValidationException::withMessages([
                'site' => __('This site has child sites and cannot be deleted.'),
            ]);
        }

        if ($target->employees()->exists()) {
            throw ValidationException::withMessages([
                'site' => __('This site is assigned to employees and cannot be deleted.'),
            ]);
        }
    }

    /**
     * Generate the site code at final approval when the maker left it
     * blank, mirroring the controller's in-transaction generation.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function prepareCreate(ApprovalRequest $request, array $payload): array
    {
        if (($payload['code'] ?? '') === '') {
            $payload['code'] = Site::generateCode();
        }

        return $payload;
    }
}
