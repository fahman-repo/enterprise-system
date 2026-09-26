<?php

namespace App\Services\Approvals;

use App\Models\ApprovalRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

abstract class CrudApprovalModule implements ApprovalModule
{
    /**
     * Persisted columns snapshotted into request payloads.
     *
     * @return list<string>
     */
    abstract protected function fields(): array;

    /**
     * @return class-string<Model>
     */
    abstract protected function modelClass(): string;

    /**
     * Invariant rules re-checked at submission and at final approval.
     *
     * Uniqueness, foreign-key existence, enum membership, and tree
     * integrity belong here. Form-shape rules (max lengths, date
     * comparisons, required file uploads) stay in the controllers.
     *
     * @return array<string, mixed>
     */
    abstract protected function rules(?Model $target, string $action): array;

    public function target(?int $targetId, bool $lockForUpdate = false): ?Model
    {
        if ($targetId === null) {
            return null;
        }

        $modelClass = $this->modelClass();
        $query = $modelClass::query()->whereKey($targetId);

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    public function beforePayload(?Model $target): ?array
    {
        if (! $this->isTarget($target)) {
            return null;
        }

        return [
            ...$this->snapshot($target),
            'updated_at' => $target->updated_at?->toDateTimeString(),
        ];
    }

    public function validateSubmission(string $action, ?Model $target, array $payload): void
    {
        if ($action === ApprovalRequest::ACTION_CREATE) {
            $this->validatePayload($payload, null, $action);
            $this->assertApplicable(null, $target, $action, $payload);

            return;
        }

        if (! $this->isTarget($target)) {
            throw $this->missingTargetException();
        }

        if ($action === ApprovalRequest::ACTION_UPDATE) {
            $this->validatePayload($payload, $target, $action);
            $this->assertApplicable(null, $target, $action, $payload);

            return;
        }

        if ($action === ApprovalRequest::ACTION_DELETE) {
            $this->assertDeletable($target);
            $this->assertApplicable(null, $target, $action, $payload);
        }
    }

    public function preview(string $action, ?Model $target, array $payload): array
    {
        return [
            'action' => $action,
            'target_label' => $this->targetLabel(),
            'before' => $this->beforePayload($target),
            'proposed' => $payload,
        ];
    }

    public function assertCanApply(ApprovalRequest $request, ?Model $target): void
    {
        if ($request->action !== ApprovalRequest::ACTION_CREATE && ! $this->isTarget($target)) {
            throw ValidationException::withMessages(['approval' => $this->missingTargetMessage()]);
        }

        if ($request->action === ApprovalRequest::ACTION_UPDATE) {
            $this->assertSnapshotMatches($request, $target);
            $this->validatePayload($request->proposed_payload, $target, $request->action);
        }

        if ($request->action === ApprovalRequest::ACTION_DELETE) {
            $this->assertSnapshotMatches($request, $target);

            try {
                $this->assertDeletable($target);
            } catch (ValidationException $exception) {
                throw $this->rekeyAsApproval($exception);
            }
        }

        if ($request->action === ApprovalRequest::ACTION_CREATE) {
            $this->validatePayload($request->proposed_payload, null, $request->action);
        }

        $this->assertApplicable($request, $target, $request->action, $request->proposed_payload);
    }

    public function apply(ApprovalRequest $request, ?Model $target): ?Model
    {
        if ($request->action === ApprovalRequest::ACTION_CREATE) {
            $modelClass = $this->modelClass();

            return $modelClass::query()->create($this->prepareCreate($request, $request->proposed_payload));
        }

        if ($request->action === ApprovalRequest::ACTION_UPDATE && $this->isTarget($target)) {
            $this->applyUpdate($request, $target, $request->proposed_payload);

            return $target;
        }

        if ($request->action === ApprovalRequest::ACTION_DELETE && $this->isTarget($target)) {
            $this->applyDelete($request, $target);
        }

        return $target;
    }

    public function discard(ApprovalRequest $request): void
    {
        // Files or side effects created at submission are removed here;
        // most modules store nothing until apply().
    }

    /**
     * Payload snapshot of the current record, stored as before_payload.
     *
     * @return array<string, mixed>
     */
    protected function snapshot(Model $target): array
    {
        return $target->only($this->fields());
    }

    /**
     * Guard run when a delete is submitted and again at final approval.
     * Throw a ValidationException keyed by the controller's error key.
     */
    protected function assertDeletable(Model $target): void {}

    /**
     * Extra guards run at submission (with a null request) and at final
     * approval. Maker-dependent checks must only run when the request
     * is available, because submission has no request yet.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function assertApplicable(?ApprovalRequest $request, ?Model $target, string $action, array $payload): void {}

    /**
     * Complete the payload for a create request, filling generated
     * columns such as sequence numbers or codes.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function prepareCreate(ApprovalRequest $request, array $payload): array
    {
        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function applyUpdate(ApprovalRequest $request, Model $target, array $payload): void
    {
        $target->update($payload);
    }

    protected function applyDelete(ApprovalRequest $request, Model $target): void
    {
        $target->delete();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function validatePayload(array $payload, ?Model $target, string $action): void
    {
        Validator::make($payload, $this->rules($target, $action))->validate();
    }

    /**
     * Compare the request's snapshot with the current record, so a
     * stale approval never overwrites a newer change.
     */
    protected function assertSnapshotMatches(ApprovalRequest $request, ?Model $target): void
    {
        $beforeTimestamp = $request->before_payload['updated_at'] ?? null;
        $currentTimestamp = $this->isTarget($target) ? $target->updated_at?->toDateTimeString() : null;

        $attributesMatch = $this->isTarget($target) && collect($this->fields())
            ->every(fn (string $attribute): bool => ($request->before_payload[$attribute] ?? null) === $this->snapshotValue($target->getAttribute($attribute)));

        if ($beforeTimestamp !== $currentTimestamp || ! $attributesMatch) {
            throw ValidationException::withMessages([
                'approval' => __('The :label changed after this request was submitted. Review and submit a new request.', [
                    'label' => mb_strtolower($this->targetLabel()),
                ]),
            ]);
        }
    }

    /**
     * Normalize a current attribute through the same JSON round-trip the
     * payload column applies, so date casts compare by value instead of
     * by object identity.
     */
    protected function snapshotValue(mixed $value): mixed
    {
        return json_decode(json_encode($value), true);
    }

    /**
     * Error bag key used by the module controller's form errors,
     * e.g. "grade" for grades and "employment_status" for employment-statuses.
     */
    protected function errorKey(): string
    {
        return str_replace(' ', '_', mb_strtolower($this->targetLabel()));
    }

    protected function missingTargetMessage(): string
    {
        return __('The :label no longer exists.', ['label' => mb_strtolower($this->targetLabel())]);
    }

    protected function missingTargetException(): ValidationException
    {
        return ValidationException::withMessages([$this->errorKey() => $this->missingTargetMessage()]);
    }

    /**
     * Re-key an apply-time guard failure as a request-level error.
     */
    protected function rekeyAsApproval(ValidationException $exception): ValidationException
    {
        return ValidationException::withMessages([
            'approval' => collect($exception->errors())->flatten()->first(),
        ]);
    }

    protected function isTarget(?Model $target): bool
    {
        $modelClass = $this->modelClass();

        return $target instanceof $modelClass;
    }
}
