<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            // Track current approval level: 1=koordinator, 2=manajer, 3=admin
            $table->unsignedTinyInteger('current_approval_level')->default(1)->after('status');

            // Coordinator approval
            $table->unsignedBigInteger('coordinator_approved_by')->nullable()->after('current_approval_level');
            $table->timestamp('coordinator_approved_at')->nullable()->after('coordinator_approved_by');
            $table->text('coordinator_notes')->nullable()->after('coordinator_approved_at');

            // Manager approval
            $table->unsignedBigInteger('manager_approved_by')->nullable()->after('coordinator_notes');
            $table->timestamp('manager_approved_at')->nullable()->after('manager_approved_by');
            $table->text('manager_notes')->nullable()->after('manager_approved_at');

            // Foreign keys
            $table->foreign('coordinator_approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('manager_approved_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('overtime_requests', function (Blueprint $table) {
            $table->unsignedTinyInteger('current_approval_level')->default(1)->after('status');

            $table->unsignedBigInteger('coordinator_approved_by')->nullable()->after('current_approval_level');
            $table->timestamp('coordinator_approved_at')->nullable()->after('coordinator_approved_by');
            $table->text('coordinator_notes')->nullable()->after('coordinator_approved_at');

            $table->unsignedBigInteger('manager_approved_by')->nullable()->after('coordinator_notes');
            $table->timestamp('manager_approved_at')->nullable()->after('manager_approved_by');
            $table->text('manager_notes')->nullable()->after('manager_approved_at');

            $table->foreign('coordinator_approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('manager_approved_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropForeign(['coordinator_approved_by']);
            $table->dropForeign(['manager_approved_by']);
            $table->dropColumn([
                'current_approval_level',
                'coordinator_approved_by', 'coordinator_approved_at', 'coordinator_notes',
                'manager_approved_by', 'manager_approved_at', 'manager_notes',
            ]);
        });

        Schema::table('overtime_requests', function (Blueprint $table) {
            $table->dropForeign(['coordinator_approved_by']);
            $table->dropForeign(['manager_approved_by']);
            $table->dropColumn([
                'current_approval_level',
                'coordinator_approved_by', 'coordinator_approved_at', 'coordinator_notes',
                'manager_approved_by', 'manager_approved_at', 'manager_notes',
            ]);
        });
    }
};
