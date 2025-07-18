<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('employee_positions');
        Schema::dropIfExists('positions');
    }

    public function down(): void
    {
        // No down migration, as structure is removed from codebase
    }
}; 