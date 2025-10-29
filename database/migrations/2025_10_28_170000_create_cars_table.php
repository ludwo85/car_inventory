<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('registration_number')->nullable();
            $table->boolean('is_registered')->default(false);
            $table->timestamps();

            $table->index('name', 'cars_name_index');
            $table->index('is_registered', 'cars_is_registered_index');
            $table->index('registration_number', 'cars_registration_number_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};
