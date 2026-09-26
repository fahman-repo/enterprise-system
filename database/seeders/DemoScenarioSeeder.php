<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Events\NullDispatcher;
use Illuminate\Support\Facades\Auth;

class DemoScenarioSeeder extends Seeder
{
    /**
     * Build the demo company used to simulate every module end to end:
     * a role and logins per division, the approval matrices, the approval
     * history, the training calendar with its rosters, benefit enrollments
     * and claims, and the authentication audit trail.
     *
     * Runs after the reference seeders. The admin is set as the acting
     * user for the whole run so audited rows carry a realistic causer
     * without starting a web session.
     */
    public function run(): void
    {
        $admin = User::query()
            ->whereHas('role', fn ($query) => $query->where('slug', 'admin'))
            ->orderBy('id')
            ->first();

        if ($admin !== null) {
            Auth::setUser($admin);
        }

        // The DatabaseSeeder runs inside Model::withoutEvents, which swaps in a
        // NullDispatcher. The demo company is the subject of the audit trail,
        // so the real dispatcher is restored for this run and put back after.
        $dispatcher = Model::getEventDispatcher();
        $muted = $dispatcher instanceof NullDispatcher;

        if ($muted) {
            Model::setEventDispatcher(app('events'));
        }

        try {
            $this->call([
                RoleSeeder::class,
                UserSeeder::class,
                EmployeeVariationSeeder::class,
                ApprovalMatrixSeeder::class,
                DevelopmentProgramSeeder::class,
                BenefitEnrollmentSeeder::class,
                BenefitClaimSeeder::class,
                ApprovalRequestSeeder::class,
                DevelopmentEnrollmentSeeder::class,
                ActivityLogSeeder::class,
            ]);
        } finally {
            if ($muted) {
                Model::setEventDispatcher($dispatcher);
            }

            Auth::guard()->forgetUser();
        }
    }
}
