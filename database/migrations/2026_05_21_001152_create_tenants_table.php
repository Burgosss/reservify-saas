<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // El slug es lo que va en el subdominio: "barberia" en barberia.tuapp.com
            // unique() garantiza que no haya dos tenants con el mismo subdominio
            $table->string('slug', 100)->unique();

            $table->string('name', 150);
            $table->enum('plan', ['free', 'pro', 'enterprise'])->default('free');
            $table->boolean('is_active')->default(true);

            // JSON flexible para configuraciones futuras por tenant
            // (horario de atención, color del tema, etc.)
            $table->json('settings')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
