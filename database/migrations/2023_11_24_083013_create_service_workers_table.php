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
        Schema::create('service_workers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_id');
            $table->unsignedBigInteger('service_worker_id');

            $table->foreign('service_id')
                ->references('id')
                ->on('services')
                ->cascadeOnUpdate()
                ->noActionOnDelete();

            $table->foreign('service_worker_id')
                ->references('id')
                ->on('users')
                ->cascadeOnUpdate()
                ->noActionOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_workers');
    }
};
