<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Map of the previous string statuses to their new integer values.
     *
     * @var array<string, int>
     */
    protected array $statuses = [
        'created' => 0,
        'processing' => 1,
        'approved' => 2,
        'rejected' => 3,
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->convert(
            fn (Blueprint $table) => $table->unsignedTinyInteger('status_tmp')->nullable()->after('role'),
            $this->statuses,
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->convert(
            fn (Blueprint $table) => $table->string('status_tmp')->nullable()->after('role'),
            array_flip($this->statuses),
        );
    }

    /**
     * Replace the status column with a new column type, mapping the existing values.
     *
     * @param  callable(Blueprint): mixed  $addColumn
     * @param  array<array-key, array-key>  $map
     */
    protected function convert(callable $addColumn, array $map): void
    {
        Schema::table('users', fn (Blueprint $table) => $addColumn($table));

        foreach ($map as $from => $to) {
            DB::table('users')->where('status', $from)->update(['status_tmp' => $to]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('status_tmp', 'status');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('status');
        });
    }
};
