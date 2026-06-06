<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('tenant_id');
            $table->foreign('tenant_id')
                  ->references('id')
                  ->on('tenants')
                  ->onDelete('cascade');

            $table->uuid('service_id');
            $table->foreign('service_id')
                  ->references('id')
                  ->on('services');

            $table->uuid('staff_user_id');
            $table->foreign('staff_user_id')
                  ->references('id')
                  ->on('users');

            $table->uuid('client_user_id');
            $table->foreign('client_user_id')
                  ->references('id')
                  ->on('users');

            $table->dateTime('starts_at');
            $table->dateTime('ends_at');

            $table->enum('status', [
                'pending',      // recién creada, esperando confirmación
                'confirmed',    // owner/staff la confirmó
                'cancelled',    // cancelada por cualquiera
                'completed',    // el servicio ya se realizó
            ])->default('pending');

            $table->text('notes')->nullable();
            $table->timestamps();

            // Índices para las queries más frecuentes
            $table->index(['tenant_id', 'status']);       // filtrar por estado
            $table->index(['staff_user_id', 'starts_at']); // ver agenda del staff
            $table->index(['tenant_id', 'starts_at']);    // dashboard por fecha
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
