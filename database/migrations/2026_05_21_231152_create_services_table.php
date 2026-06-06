<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('tenant_id');
            $table->foreign('tenant_id')
                  ->references('id')
                  ->on('tenants')
                  ->onDelete('cascade');

            $table->string('name', 150);
            $table->text('description')->nullable();

            // Duración en minutos: 30, 45, 60, etc.
            // Sirve para calcular ends_at al crear un booking
            $table->unsignedInteger('duration_min')->default(30);

            $table->decimal('price', 10, 2);
            $table->char('currency', 3)->default('MXN');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
