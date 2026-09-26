<?php

namespace App\Services\Approvals;

use App\Models\ApprovalRequest;
use Illuminate\Database\Eloquent\Model;

interface ApprovalModule
{
    public function key(): string;

    public function label(): string;

    public function targetLabel(): string;

    public function target(?int $targetId, bool $lockForUpdate = false): ?Model;

    /** @return array<string, mixed>|null */
    public function beforePayload(?Model $target): ?array;

    /** @param array<string, mixed> $payload */
    public function validateSubmission(string $action, ?Model $target, array $payload): void;

    /** @param array<string, mixed> $payload */
    public function preview(string $action, ?Model $target, array $payload): array;

    public function assertCanApply(ApprovalRequest $request, ?Model $target): void;

    public function apply(ApprovalRequest $request, ?Model $target): ?Model;

    /**
     * Clean up side effects created when the request was submitted but
     * never applied, such as an uploaded file stored for review.
     */
    public function discard(ApprovalRequest $request): void;
}
