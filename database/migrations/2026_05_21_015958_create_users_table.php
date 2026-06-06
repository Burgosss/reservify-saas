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
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Clave foránea al tenant dueño de este usuario
            $table->uuid('tenant_id');
            $table->foreign('tenant_id')
                  ->references('id')
                  ->on('tenants')
                  ->onDelete('cascade'); // si se borra el tenant, se borran sus usuarios

            $table->string('name', 150);
            $table->string('email', 200);
            $table->string('password');
            $table->enum('role', ['owner', 'staff', 'client'])->default('client');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // El mismo email puede existir en tenants distintos
            // pero no dos veces dentro del mismo tenant
            $table->unique(['email', 'tenant_id']);

            // Índice para acelerar las queries filtradas por tenant
            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
