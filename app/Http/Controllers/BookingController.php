<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $bookings = Booking::with([
                        'service:id,name,duration_min,price',
                        'staff:id,name',
                        'client:id,name,email',
                    ])
                    ->when($request->status, fn($q, $s) =>
                        $q->where('status', $s)
                    )
                    ->when($request->date, fn($q, $d) =>
                        $q->whereDate('starts_at', $d)
                    )
                    ->when($request->staff_id, fn($q, $id) =>
                        $q->where('staff_user_id', $id)
                    )
                    ->orderBy('starts_at')
                    ->paginate(20);

        return response()->json($bookings);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'service_id'    => 'required|uuid',
            'staff_user_id' => 'required|uuid',
            'starts_at'     => 'required|date|after:now',
            'notes'         => 'nullable|string|max:500',
        ]);

        // 1. Verificar que el servicio existe en este tenant
        $service = Service::find($data['service_id']);
        if (! $service) {
            return response()->json([
                'error' => 'Servicio no encontrado.',
                'code'  => 'SERVICE_NOT_FOUND',
            ], 404);
        }

        // 2. Verificar que el staff existe en este tenant
        $staff = User::where('id', $data['staff_user_id'])
                     ->whereIn('role', ['owner', 'staff'])
                     ->first();
        if (! $staff) {
            return response()->json([
                'error' => 'Staff no encontrado.',
                'code'  => 'STAFF_NOT_FOUND',
            ], 404);
        }

        // 3. Calcular ends_at según duración del servicio
        $startsAt = Carbon::parse($data['starts_at']);
        $endsAt   = $startsAt->copy()->addMinutes($service->duration_min);

        // 4. Detectar conflicto de horario
        // Un conflicto existe cuando la nueva reserva se superpone
        // con cualquier reserva existente del mismo staff
        $conflict = Booking::where('staff_user_id', $data['staff_user_id'])
            ->whereIn('status', ['pending', 'confirmed'])
            ->where(function ($q) use ($startsAt, $endsAt) {
                $q->where(function ($q2) use ($startsAt, $endsAt) {
                    // Caso 1: la nueva reserva empieza dentro de una existente
                    $q2->where('starts_at', '<', $endsAt)
                       ->where('ends_at', '>', $startsAt);
                });
            })
            ->exists();

        if ($conflict) {
            return response()->json([
                'error' => 'El horario no está disponible.',
                'code'  => 'SLOT_CONFLICT',
            ], 409);
        }

        // 5. Crear la reserva
        $booking = Booking::create([
            'service_id'     => $data['service_id'],
            'staff_user_id'  => $data['staff_user_id'],
            'client_user_id' => auth()->id(),
            'starts_at'      => $startsAt,
            'ends_at'        => $endsAt,
            'status'         => 'pending',
            'notes'          => $data['notes'] ?? null,
        ]);

        return response()->json(
            $booking->load('service:id,name,price', 'staff:id,name'),
            201
        );
    }

    public function show(Booking $booking)
    {
        return response()->json(
            $booking->load('service', 'staff', 'client')
        );
    }

    // Solo owner/staff puede cambiar el status
    public function update(Request $request, Booking $booking)
    {
        $data = $request->validate([
            'status' => 'required|in:confirmed,cancelled,completed',
            'notes'  => 'nullable|string|max:500',
        ]);

        $booking->update($data);

        return response()->json($booking->load('service', 'staff', 'client'));
    }

    // Dashboard: estadísticas del tenant
    public function stats()
    {
        $today = now()->toDateString();

        return response()->json([
            'bookings_today'   => Booking::whereDate('starts_at', $today)->count(),
            'bookings_pending' => Booking::where('status', 'pending')->count(),
            'bookings_total'   => Booking::count(),
            'revenue_today'    => Booking::whereDate('starts_at', $today)
                                         ->where('status', 'completed')
                                         ->join('services', 'bookings.service_id', '=', 'services.id')
                                         ->sum('services.price'),
        ]);
    }

    // Slots disponibles para un staff en una fecha — accesible por clientes
    public function availableSlots(Request $request)
    {
        $request->validate([
            'date'     => 'required|date',
            'staff_id' => 'required|uuid',
            'service_id' => 'required|uuid',
        ]);

        $service  = Service::findOrFail($request->service_id);
        $date     = $request->date;
        $staffId  = $request->staff_id;

        // Reservas existentes ese día para ese staff
        $booked = Booking::where('staff_user_id', $staffId)
            ->whereDate('starts_at', $date)
            ->whereIn('status', ['pending', 'confirmed'])
            ->pluck('starts_at')
            ->map(fn($dt) => \Carbon\Carbon::parse($dt)->format('H:i'))
            ->toArray();

        // Horario del staff ese día de la semana
        $dayOfWeek = ((\Carbon\Carbon::parse($date)->dayOfWeek) + 6) % 7;
        $schedule  = \App\Models\StaffSchedule::where('user_id', $staffId)
                        ->where('day_of_week', $dayOfWeek)
                        ->first();

        if (! $schedule) {
            return response()->json(['slots' => []]);
        }

        // Generar slots
        $duration   = $service->duration_min;
        [$startH, $startM] = explode(':', $schedule->start_time);
        [$endH,   $endM  ] = explode(':', $schedule->end_time);

        $startTotal = (int)$startH * 60 + (int)$startM;
        $endTotal   = (int)$endH   * 60 + (int)$endM;

        $slots = [];
        for ($min = $startTotal; $min + $duration <= $endTotal; $min += $duration) {
            $slot = sprintf('%02d:%02d', intdiv($min, 60), $min % 60);
            if (! in_array($slot, $booked)) {
                $slots[] = $slot;
            }
        }

        return response()->json(['slots' => $slots]);
    }
}


