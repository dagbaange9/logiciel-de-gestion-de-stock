<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->after('email');
            $table->enum('status', [
                'email_non_verifie',
                'en_attente_validation',
                'actif',
                'refuse',
                'desactive',
            ])->default('email_non_verifie')->after('password');
            $table->foreignId('role_id')->nullable()->after('status')->constrained('roles')->restrictOnDelete();
            $table->string('pending_email')->nullable()->after('email');
            $table->timestamp('deactivated_at')->nullable()->after('status');
            $table->softDeletes();
            $table->index(['status']);
            $table->index(['role_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['role_id']);
            $table->dropConstrainedForeignId('role_id');
            $table->dropSoftDeletes();
            $table->dropColumn([
                'phone',
                'status',
                'deactivated_at',
                'pending_email',
            ]);
        });
    }
};
