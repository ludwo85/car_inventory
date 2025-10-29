<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('parts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('serialnumber');
            $table->foreignId('car_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->index('car_id', 'parts_car_id_index');
            $table->index('name', 'parts_name_index');
            $table->index('serialnumber', 'parts_serialnumber_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parts');
    }
};
