<?php

namespace Database\Seeders;

use App\Models\DevelopmentEnrollment;
use App\Models\DevelopmentProgram;
use App\Models\Employee;
use Illuminate\Database\Seeder;

class DevelopmentEnrollmentSeeder extends Seeder
{
    /**
     * Fill every programme with a deterministic roster. Outcomes follow
     * the programme lifecycle: completed events carry scores and
     * certificates, running events mix attendance with registrations,
     * planned events stay registered, and the cancelled event keeps a
     * cancelled roster for reporting. Roster size never exceeds capacity.
     */
    public function run(): void
    {
        $employeeIds = Employee::query()
            ->where('is_active', true)
            ->orderBy('employee_number')
            ->pluck('id')
            ->all();

        if ($employeeIds === []) {
            return;
        }

        $programs = DevelopmentProgram::query()->orderBy('code')->get();
        $headcount = count($employeeIds);

        foreach ($programs as $index => $program) {
            if (! $program->is_active) {
                continue;
            }

            $size = $this->rosterSize($program);
            $offset = ($index * 13) % $headcount;

            for ($seat = 0; $seat < $size; $seat++) {
                DevelopmentEnrollment::query()->updateOrCreate(
                    [
                        'development_program_id' => $program->getKey(),
                        'employee_id' => $employeeIds[($offset + $seat) % $headcount],
                    ],
                    $this->outcome($program, $index, $seat),
                );
            }
        }
    }

    /**
     * Seats taken, scaled to the lifecycle and capped by the capacity.
     */
    protected function rosterSize(DevelopmentProgram $program): int
    {
        $base = match ($program->status) {
            'completed' => 8 + ($program->getKey() % 9),
            'ongoing' => 10 + ($program->getKey() % 11),
            'planned' => 6 + ($program->getKey() % 7),
            default => 4,
        };

        $capacity = (int) ($program->capacity ?? 0);

        return $capacity > 0 ? min($base, $capacity) : $base;
    }

    /**
     * The enrolment row for one seat: status plus the score, completion
     * date and certificate the status implies.
     *
     * @return array<string, mixed>
     */
    protected function outcome(DevelopmentProgram $program, int $programIndex, int $seat): array
    {
        $roll = ($programIndex * 7 + $seat * 5) % 20;

        if ($program->status === 'cancelled') {
            return [
                'status' => 'cancelled',
                'score' => null,
                'completed_at' => null,
                'certificate_no' => null,
                'notes' => 'Cancelled by the organizer.',
            ];
        }

        if ($program->status === 'completed') {
            $completedOn = $program->end_date;

            if ($roll < 3) {
                return [
                    'status' => 'failed',
                    'score' => 55 + (($programIndex + $seat * 3) % 15),
                    'completed_at' => $completedOn,
                    'certificate_no' => null,
                    'notes' => 'Did not reach the passing grade.',
                ];
            }

            if ($roll < 5) {
                return [
                    'status' => 'attended',
                    'score' => null,
                    'completed_at' => null,
                    'certificate_no' => null,
                    'notes' => 'Attendance only, no assessment.',
                ];
            }

            return [
                'status' => 'completed',
                'score' => 78 + (($programIndex * 3 + $seat * 7) % 19),
                'completed_at' => $completedOn,
                'certificate_no' => sprintf('CERT-%s-%04d', $completedOn->format('Y'), $programIndex * 100 + $seat + 1),
                'notes' => null,
            ];
        }

        if ($program->status === 'ongoing') {
            return [
                'status' => $roll < 8 ? 'attended' : 'registered',
                'score' => null,
                'completed_at' => null,
                'certificate_no' => null,
                'notes' => null,
            ];
        }

        return [
            'status' => 'registered',
            'score' => null,
            'completed_at' => null,
            'certificate_no' => null,
            'notes' => 'Seat reserved.',
        ];
    }
}
