<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Map of the previous string roles to their new integer values.
     *
     * @var array<string, int>
     */
    protected array $roles = [
        'admin' => 0,
        'hr' => 1,
        'candidate' => 2,
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('role_value')->default(2)->after('email');
            $table->string('status')->nullable()->index()->after('role_value');
        });

        foreach ($this->roles as $name => $value) {
            DB::table('users')->where('role', $name)->update(['role_value' => $value]);
        }

        DB::table('users')->where('role_value', 2)->update(['status' => 'created']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn('role');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('role_value', 'role');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role_name')->default('candidate')->after('email');
        });

        foreach ($this->roles as $name => $value) {
            DB::table('users')->where('role', $value)->update(['role_name' => $name]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropIndex(['status']);
            $table->dropColumn(['role', 'status']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('role_name', 'role');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('role');
        });
    }
};
