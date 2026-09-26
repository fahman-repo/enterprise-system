<?php

namespace Database\Seeders;

use App\Models\DevelopmentProgram;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DevelopmentProgramSeeder extends Seeder
{
    /**
     * Seed a realistic training calendar: planned events still ahead,
     * programmes running this month, a finished back-catalogue with
     * outcomes, one cancellation and one archived programme. Dates are
     * relative to today so the demo never goes stale.
     */
    public function run(): void
    {
        foreach (self::definitions() as $index => $row) {
            $start = Carbon::today()->addDays($row['start']);

            DevelopmentProgram::query()->updateOrCreate(
                ['code' => sprintf('DEV-%05d', $index + 1)],
                [
                    'name' => $row['name'],
                    'type' => $row['type'],
                    'description' => $row['description'],
                    'organizer' => $row['organizer'],
                    'location' => $row['location'],
                    'start_date' => $start->toDateString(),
                    'end_date' => $start->copy()->addDays($row['duration'])->toDateString(),
                    'capacity' => $row['capacity'],
                    'cost' => $row['cost'],
                    'status' => $row['status'],
                    'is_active' => $row['is_active'] ?? true,
                ],
            );
        }
    }

    /**
     * The training calendar: offset in days from today, duration in days,
     * and the lifecycle status the dates imply.
     *
     * @return list<array<string, mixed>>
     */
    public static function definitions(): array
    {
        return [
            ['name' => 'Leadership Fundamentals for Supervisors', 'type' => 'training', 'description' => 'First-line leadership, delegation and performance conversations.', 'organizer' => 'Internal HR Academy', 'location' => 'Jakarta Tower', 'start' => 10, 'duration' => 2, 'capacity' => 30, 'cost' => '45000000.00', 'status' => 'planned'],
            ['name' => 'K3 & HSE Refresher Certification', 'type' => 'certification', 'description' => 'Statutory workplace safety refresher with certificate renewal.', 'organizer' => 'Bina K3 Consulting', 'location' => 'Surabaya Plant', 'start' => 21, 'duration' => 2, 'capacity' => 40, 'cost' => '25000000.00', 'status' => 'planned'],
            ['name' => 'Sales Negotiation Workshop', 'type' => 'workshop', 'description' => 'Value-based negotiation practice for field and channel sales.', 'organizer' => 'Prosell Indonesia', 'location' => 'Bandung Office Building', 'start' => 35, 'duration' => 1, 'capacity' => 24, 'cost' => '18000000.00', 'status' => 'planned'],
            ['name' => 'Digital Marketing Webinar Series', 'type' => 'webinar', 'description' => 'Campaign analytics, content operations and lead scoring.', 'organizer' => 'Marketing Guild', 'location' => 'Online', 'start' => 45, 'duration' => 0, 'capacity' => 60, 'cost' => '5000000.00', 'status' => 'planned'],
            ['name' => 'ISO 9001:2015 Internal Auditor Training', 'type' => 'training', 'description' => 'Internal auditor qualification for the quality management system.', 'organizer' => 'Quality Systems Institute', 'location' => 'Surabaya Plant', 'start' => 60, 'duration' => 2, 'capacity' => 20, 'cost' => '32000000.00', 'status' => 'planned'],
            ['name' => 'Onboarding Program Batch 12', 'type' => 'training', 'description' => 'Company induction for the newest hires.', 'organizer' => 'HR Operations', 'location' => 'Jakarta Tower', 'start' => -14, 'duration' => 21, 'capacity' => 50, 'cost' => '0.00', 'status' => 'ongoing'],
            ['name' => 'Lean Manufacturing Coaching', 'type' => 'coaching', 'description' => 'Gemba coaching on waste elimination across the assembly lines.', 'organizer' => 'Kaizen Partners', 'location' => 'Surabaya Plant', 'start' => -30, 'duration' => 60, 'capacity' => 15, 'cost' => '60000000.00', 'status' => 'ongoing'],
            ['name' => 'Data Analysis with Python', 'type' => 'training', 'description' => 'Pandas and reporting automation for analysts.', 'organizer' => 'Data Academy', 'location' => 'Online', 'start' => -21, 'duration' => 35, 'capacity' => 20, 'cost' => '28000000.00', 'status' => 'ongoing'],
            ['name' => 'Ice Breaking: Team Retreat Batch 4', 'type' => 'ice_breaking', 'description' => 'Cross-division retreat to open the quarterly cycle.', 'organizer' => 'Outbound Indonesia', 'location' => 'Bogor', 'start' => -7, 'duration' => 9, 'capacity' => 80, 'cost' => '75000000.00', 'status' => 'ongoing'],
            ['name' => 'Effective Communication Skills', 'type' => 'training', 'description' => 'Structured communication and listening for individual contributors.', 'organizer' => 'Internal HR Academy', 'location' => 'Jakarta Tower', 'start' => -120, 'duration' => 2, 'capacity' => 40, 'cost' => '20000000.00', 'status' => 'completed'],
            ['name' => 'Financial Literacy for Non-Finance Managers', 'type' => 'seminar', 'description' => 'Reading profit and loss, budgets and cost drivers.', 'organizer' => 'Finance Institute', 'location' => 'Jakarta Tower', 'start' => -100, 'duration' => 1, 'capacity' => 30, 'cost' => '24000000.00', 'status' => 'completed'],
            ['name' => 'Advanced Excel for Reporting', 'type' => 'workshop', 'description' => 'Pivot models, lookup patterns and dashboard basics.', 'organizer' => 'Excelindo', 'location' => 'Bandung Office Building', 'start' => -90, 'duration' => 1, 'capacity' => 25, 'cost' => '15000000.00', 'status' => 'completed'],
            ['name' => 'Welding Safety Recertification', 'type' => 'certification', 'description' => 'Recertification for the fabrication and assembly crews.', 'organizer' => 'Bina K3 Consulting', 'location' => 'Surabaya Plant', 'start' => -150, 'duration' => 2, 'capacity' => 35, 'cost' => '40000000.00', 'status' => 'completed'],
            ['name' => 'Customer Service Excellence', 'type' => 'training', 'description' => 'Complaint handling and service recovery standards.', 'organizer' => 'Service Plus', 'location' => 'Cikarang Warehouse', 'start' => -80, 'duration' => 1, 'capacity' => 30, 'cost' => '18000000.00', 'status' => 'completed'],
            ['name' => 'Statistical Process Control for QC', 'type' => 'training', 'description' => 'Control charts and process capability for inspectors.', 'organizer' => 'Quality Systems Institute', 'location' => 'Surabaya Plant', 'start' => -60, 'duration' => 2, 'capacity' => 22, 'cost' => '26000000.00', 'status' => 'completed'],
            ['name' => 'Cybersecurity Awareness', 'type' => 'webinar', 'description' => 'Phishing, data handling and incident reporting basics.', 'organizer' => 'SecureNet', 'location' => 'Online', 'start' => -45, 'duration' => 0, 'capacity' => 100, 'cost' => '3000000.00', 'status' => 'completed'],
            ['name' => 'Product Knowledge for Sales', 'type' => 'seminar', 'description' => 'Catalogue deep dive for the commercial teams.', 'organizer' => 'Commercial Division', 'location' => 'Jakarta Branch', 'start' => -30, 'duration' => 1, 'capacity' => 45, 'cost' => '12000000.00', 'status' => 'completed'],
            ['name' => 'Vendor Negotiation Bootcamp', 'type' => 'workshop', 'description' => 'Postponed after the procurement policy review.', 'organizer' => 'Procurement Institute', 'location' => 'Jakarta Tower', 'start' => 14, 'duration' => 2, 'capacity' => 20, 'cost' => '22000000.00', 'status' => 'cancelled'],
            ['name' => 'Legacy Leadership Program 2019', 'type' => 'training', 'description' => 'Archived curriculum, kept for reporting history.', 'organizer' => 'Internal HR Academy', 'location' => 'Jakarta Tower', 'start' => -900, 'duration' => 3, 'capacity' => 25, 'cost' => '15000000.00', 'status' => 'completed', 'is_active' => false],
        ];
    }
}
