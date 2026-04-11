<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        User::firstOrCreate(
            ['email' => 'admin@storagecrm.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Facility
        $facility = Facility::create([
            'name' => 'SecureStore Downtown',
            'address' => '1234 Main Street, Suite 100, Springfield, IL 62701',
            'phone' => '(555) 123-4567',
            'email' => 'info@securestore.com',
            'late_fee_type' => 'flat',
            'late_fee_amount' => 25.00,
            'late_fee_grace_days' => 5,
            'webhook_url' => null,
            'is_active' => true,
        ]);

        // Units with grid positions
        $sizes = [
            ['size' => '5x5',  'price' => 45.00,  'label' => 'Small Closet'],
            ['size' => '5x10', 'price' => 75.00,  'label' => 'Half Garage'],
            ['size' => '10x10','price' => 120.00, 'label' => 'Standard'],
            ['size' => '10x15','price' => 160.00, 'label' => 'Large'],
            ['size' => '10x20','price' => 200.00, 'label' => 'Extra Large'],
            ['size' => '10x30','price' => 275.00, 'label' => 'Warehouse'],
        ];

        $units = [];
        $unitNumber = 101;
        for ($row = 0; $row < 5; $row++) {
            for ($col = 0; $col < 8; $col++) {
                $sizeInfo = $sizes[array_rand($sizes)];
                $units[] = Unit::create([
                    'facility_id' => $facility->id,
                    'unit_number' => (string) $unitNumber,
                    'size' => $sizeInfo['size'],
                    'size_label' => $sizeInfo['label'],
                    'monthly_price' => $sizeInfo['price'],
                    'status' => 'available',
                    'position_x' => $col,
                    'position_y' => $row,
                ]);
                $unitNumber++;
            }
        }

        // Tenants
        $tenantsData = [
            ['first_name' => 'John',    'last_name' => 'Anderson',  'email' => 'john.anderson@email.com',   'phone' => '(555) 234-5678'],
            ['first_name' => 'Sarah',   'last_name' => 'Mitchell',  'email' => 'sarah.m@email.com',         'phone' => '(555) 345-6789'],
            ['first_name' => 'Michael', 'last_name' => 'Thompson',  'email' => 'mike.t@email.com',          'phone' => '(555) 456-7890'],
            ['first_name' => 'Emily',   'last_name' => 'Davis',     'email' => 'emily.davis@email.com',     'phone' => '(555) 567-8901'],
            ['first_name' => 'Robert',  'last_name' => 'Wilson',    'email' => 'rob.wilson@email.com',      'phone' => '(555) 678-9012'],
            ['first_name' => 'Jessica', 'last_name' => 'Brown',     'email' => 'jess.brown@email.com',      'phone' => '(555) 789-0123'],
            ['first_name' => 'David',   'last_name' => 'Garcia',    'email' => 'david.garcia@email.com',    'phone' => '(555) 890-1234'],
            ['first_name' => 'Lisa',    'last_name' => 'Martinez',  'email' => 'lisa.martinez@email.com',   'phone' => '(555) 901-2345'],
            ['first_name' => 'James',   'last_name' => 'Rodriguez', 'email' => 'james.r@email.com',         'phone' => '(555) 012-3456'],
            ['first_name' => 'Amanda',  'last_name' => 'Hernandez', 'email' => 'amanda.h@email.com',        'phone' => '(555) 123-4567'],
            ['first_name' => 'Chris',   'last_name' => 'Lee',       'email' => 'chris.lee@email.com',       'phone' => '(555) 234-5679'],
            ['first_name' => 'Nancy',   'last_name' => 'Walker',    'email' => 'nancy.walker@email.com',    'phone' => '(555) 345-6780'],
            ['first_name' => 'Daniel',  'last_name' => 'Hall',      'email' => 'dan.hall@email.com',        'phone' => '(555) 456-7891'],
            ['first_name' => 'Karen',   'last_name' => 'Allen',     'email' => 'karen.allen@email.com',     'phone' => '(555) 567-8902'],
            ['first_name' => 'Brian',   'last_name' => 'Young',     'email' => 'brian.young@email.com',     'phone' => '(555) 678-9013'],
        ];

        $tenants = [];
        foreach ($tenantsData as $data) {
            $tenants[] = Tenant::create(array_merge($data, [
                'emergency_contact_name' => 'Emergency Contact',
                'emergency_contact_phone' => '(555) 999-0000',
            ]));
        }

        // Create leases and invoices for a subset of tenants
        $moveInDates = [
            Carbon::now()->subMonths(6)->day(5),
            Carbon::now()->subMonths(4)->day(12),
            Carbon::now()->subMonths(3)->day(18),
            Carbon::now()->subMonths(8)->day(3),
            Carbon::now()->subMonths(2)->day(22),
            Carbon::now()->subMonths(5)->day(7),
            Carbon::now()->subMonths(1)->day(15),
            Carbon::now()->subMonths(7)->day(28),
            Carbon::now()->subMonths(10)->day(10),
            Carbon::now()->subMonths(9)->day(1),
            Carbon::now()->subMonths(3)->day(25),
            Carbon::now()->subMonths(2)->day(9),
        ];

        $occupiedUnits = array_slice($units, 0, 12);

        foreach ($occupiedUnits as $i => $unit) {
            $tenant = $tenants[$i];
            $moveIn = $moveInDates[$i];

            $lease = Lease::create([
                'unit_id' => $unit->id,
                'tenant_id' => $tenant->id,
                'move_in_date' => $moveIn,
                'monthly_rate' => $unit->monthly_price,
                'billing_day' => $moveIn->day,
                'status' => 'active',
            ]);

            // Create past invoices
            $invoiceDate = $moveIn->copy();
            while ($invoiceDate->lt(now())) {
                $periodStart = $invoiceDate->copy();
                $periodEnd = $invoiceDate->copy()->addMonth()->subDay();
                $dueDate = $periodStart->copy();

                $isPaid = $invoiceDate->lt(now()->subDays(10));
                $isOverdue = ! $isPaid && $dueDate->lt(now()->subDays(5));

                $lateFee = $isOverdue ? 25.00 : 0;
                $total = $unit->monthly_price + $lateFee;

                $invoice = Invoice::create([
                    'lease_id' => $lease->id,
                    'tenant_id' => $tenant->id,
                    'amount' => $unit->monthly_price,
                    'late_fee' => $lateFee,
                    'total' => $total,
                    'due_date' => $dueDate,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'status' => $isPaid ? 'paid' : ($isOverdue ? 'overdue' : 'pending'),
                    'paid_at' => $isPaid ? $dueDate->copy()->addDays(rand(0, 3)) : null,
                ]);

                if ($isPaid) {
                    Payment::create([
                        'invoice_id' => $invoice->id,
                        'amount' => $total,
                        'method' => ['cash', 'card', 'bank_transfer', 'check'][array_rand(['cash', 'card', 'bank_transfer', 'check'])],
                        'reference' => 'PMT-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT),
                        'paid_at' => $invoice->paid_at,
                    ]);
                }

                $invoiceDate->addMonth();
            }
        }

        // Set some units to maintenance
        $units[35]->update(['status' => 'maintenance']);
        $units[38]->update(['status' => 'maintenance']);

        $this->command->info('Demo data seeded successfully!');
        $this->command->info('Login: admin@storagecrm.com / password');
    }
}
