<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Service;
use App\Models\StaffSchedule;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        // ══════════════════════════════════════════
        // TENANT A — Barbería López
        // ══════════════════════════════════════════
        $barberia = Tenant::create([
            'slug'      => 'barberia-lopez',
            'name'      => 'Barbería López',
            'plan'      => 'pro',
            'is_active' => true,
        ]);

        // Owner
        $ownerBarberia = User::create([
            'tenant_id' => $barberia->id,
            'name'      => 'Roberto López',
            'email'     => 'owner@barberia.com',
            'password'  => 'password123',
            'role'      => 'owner',
        ]);

        // Staff
        $miguelBarberia = User::create([
            'tenant_id' => $barberia->id,
            'name'      => 'Miguel Barber',
            'email'     => 'miguel@barberia.com',
            'password'  => 'password123',
            'role'      => 'staff',
        ]);

        // Cliente
        $clienteBarberia = User::create([
            'tenant_id' => $barberia->id,
            'name'      => 'Carlos López',
            'email'     => 'carlos@barberia.com',
            'password'  => 'password123',
            'role'      => 'client',
        ]);

        // Servicios
        $corteClasico = Service::create([
            'tenant_id'    => $barberia->id,
            'name'         => 'Corte clásico',
            'description'  => 'Corte de cabello tradicional con tijera',
            'duration_min' => 30,
            'price'        => 150.00,
            'currency'     => 'MXN',
        ]);

        $corteBarba = Service::create([
            'tenant_id'    => $barberia->id,
            'name'         => 'Corte + Barba',
            'description'  => 'Corte de cabello y arreglo de barba',
            'duration_min' => 60,
            'price'        => 250.00,
            'currency'     => 'MXN',
        ]);

        $afeitado = Service::create([
            'tenant_id'    => $barberia->id,
            'name'         => 'Afeitado clásico',
            'description'  => 'Afeitado con navaja y toalla caliente',
            'duration_min' => 45,
            'price'        => 180.00,
            'currency'     => 'MXN',
        ]);

        // Horarios del staff — Lunes a Viernes
        foreach ([0, 1, 2, 3, 4] as $day) {
            StaffSchedule::create([
                'tenant_id'    => $barberia->id,
                'user_id'      => $miguelBarberia->id,
                'day_of_week'  => $day,
                'start_time'   => '09:00',
                'end_time'     => '18:00',
            ]);
        }

        // Reservas demo
        $this->createBookings($barberia, $corteClasico, $miguelBarberia, $clienteBarberia);

        // ══════════════════════════════════════════
        // TENANT B — Clínica Pérez
        // ══════════════════════════════════════════
        $clinica = Tenant::create([
            'slug'      => 'clinica-perez',
            'name'      => 'Clínica Pérez',
            'plan'      => 'free',
            'is_active' => true,
        ]);

        // Owner
        $ownerClinica = User::create([
            'tenant_id' => $clinica->id,
            'name'      => 'Dra. Ana Pérez',
            'email'     => 'owner@clinica.com',
            'password'  => 'password123',
            'role'      => 'owner',
        ]);

        // Staff
        $doctorClinica = User::create([
            'tenant_id' => $clinica->id,
            'name'      => 'Dr. Juan Martínez',
            'email'     => 'juan@clinica.com',
            'password'  => 'password123',
            'role'      => 'staff',
        ]);

        // Cliente
        $clienteClinica = User::create([
            'tenant_id' => $clinica->id,
            'name'      => 'María García',
            'email'     => 'maria@clinica.com',
            'password'  => 'password123',
            'role'      => 'client',
        ]);

        // Servicios
        $consultaGeneral = Service::create([
            'tenant_id'    => $clinica->id,
            'name'         => 'Consulta general',
            'description'  => 'Consulta médica de primera vez',
            'duration_min' => 30,
            'price'        => 500.00,
            'currency'     => 'MXN',
        ]);

        $consultaEspecialista = Service::create([
            'tenant_id'    => $clinica->id,
            'name'         => 'Consulta especialista',
            'description'  => 'Consulta con médico especialista',
            'duration_min' => 45,
            'price'        => 800.00,
            'currency'     => 'MXN',
        ]);

        // Horarios del doctor — Lunes, Miércoles y Viernes
        foreach ([0, 2, 4] as $day) {
            StaffSchedule::create([
                'tenant_id'   => $clinica->id,
                'user_id'     => $doctorClinica->id,
                'day_of_week' => $day,
                'start_time'  => '08:00',
                'end_time'    => '14:00',
            ]);
        }

        // Reservas demo
        $this->createBookings($clinica, $consultaGeneral, $doctorClinica, $clienteClinica);

        $this->command->info('Dos tenants demo creados con datos reales');
    }

    private function createBookings($tenant, $service, $staff, $client): void
    {
        $statuses = ['confirmed', 'confirmed', 'pending', 'completed', 'cancelled'];
        $baseDate = Carbon::now()->startOfWeek();

        foreach ($statuses as $i => $status) {
            $startsAt = $baseDate->copy()->addDays($i)->setHour(10)->addMinutes($i * 30);
            $endsAt   = $startsAt->copy()->addMinutes($service->duration_min);

            Booking::create([
                'tenant_id'      => $tenant->id,
                'service_id'     => $service->id,
                'staff_user_id'  => $staff->id,
                'client_user_id' => $client->id,
                'starts_at'      => $startsAt,
                'ends_at'        => $endsAt,
                'status'         => $status,
                'notes'          => 'Reserva de demostración #' . ($i + 1),
            ]);
        }
    }
}
