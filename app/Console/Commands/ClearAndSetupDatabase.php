<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\Payroll;
use App\Models\UserSchedule;
use App\Models\UserScheduleHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ClearAndSetupDatabase extends Command
{
    protected $signature = 'db:clear-setup {--yes : Skip confirmation}';
    protected $description = 'Clear all data and create admin user';

    public function handle()
    {
        if (!$this->option('yes') && !$this->confirm('Apakah Anda yakin ingin menghapus semua data?')) {
            $this->line('Dibatalkan.');
            return;
        }

        // Clear all data
        $this->line('Menghapus semua data...');
        
        // Disable foreign key checks
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        UserScheduleHistory::truncate();
        UserSchedule::truncate();
        Payroll::truncate();
        OvertimeRequest::truncate();
        LeaveRequest::truncate();
        Attendance::truncate();
        User::truncate();

        // Re-enable foreign key checks
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info('Semua data berhasil dihapus.');

        // Create admin user
        $this->line('Membuat user admin...');

        User::create([
            'name' => 'Administrator',
            'email' => 'admin@hospital.local',
            'nip' => 'admin',
            'password' => Hash::make('admin123'),
            'role' => 'admin_sdm',
            'status' => 'active',
        ]);

        $this->info('User admin berhasil dibuat:');
        $this->info('  NIP/Username: admin');
        $this->info('  Password: admin123');
    }
}
