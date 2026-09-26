<?php

namespace App\Services\Approvals;

use InvalidArgumentException;

class ApprovalModuleRegistry
{
    protected array $modules = [];

    /**
     * Create a new class instance.
     */
    public function register(ApprovalModule $module): void
    {
        $this->modules[$module->key()] = $module;
    }

    public function get(string $moduleKey): ApprovalModule
    {
        if (! isset($this->modules[$moduleKey])) {
            throw new InvalidArgumentException('Unsupported approval module.');
        }

        return $this->modules[$moduleKey];
    }

    public function all(): array
    {
        return $this->modules;
    }

    public function keys(): array
    {
        return array_keys($this->modules);
    }
}
