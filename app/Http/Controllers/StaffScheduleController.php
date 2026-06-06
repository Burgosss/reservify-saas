<?php

namespace App\Http\Controllers;

use App\Models\StaffSchedule;
use App\Models\User;
use Illuminate\Http\Request;

class StaffScheduleController extends Controller
{
    // Ver horarios de todo el staff del tenant
    public function index()
    {
        return response()->json(
            StaffSchedule::with('staff:id,name,role')
                         ->get()
        );
    }

    // Crear horario para un miembro del staff
    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id'     => 'required|uuid',
            'day_of_week' => 'required|integer|min:0|max:6',
            'start_time'  => 'required|date_format:H:i',
            'end_time'    => 'required|date_format:H:i|after:start_time',
        ]);

        // Verificar que el user_id pertenece a este tenant y es staff
        $staff = User::where('id', $data['user_id'])
                     ->whereIn('role', ['owner', 'staff'])
                     ->first();

        if (! $staff) {
            return response()->json([
                'error' => 'Usuario no encontrado o no es staff.',
                'code'  => 'INVALID_STAFF',
            ], 422);
        }

        // Verificar que no existe ya un horario para ese día y staff
        $exists = StaffSchedule::where('user_id', $data['user_id'])
                               ->where('day_of_week', $data['day_of_week'])
                               ->exists();

        if ($exists) {
            return response()->json([
                'error' => 'Ya existe un horario para ese día.',
                'code'  => 'SCHEDULE_CONFLICT',
            ], 409);
        }

        $schedule = StaffSchedule::create($data);

        return response()->json(
            $schedule->load('staff:id,name,role'),
            201
        );
    }

    public function update(Request $request, StaffSchedule $staffSchedule)
    {
        $data = $request->validate([
            'start_time' => 'sometimes|date_format:H:i',
            'end_time'   => 'sometimes|date_format:H:i|after:start_time',
        ]);

        $staffSchedule->update($data);

        return response()->json($staffSchedule);
    }

    public function destroy(StaffSchedule $staffSchedule)
    {
        $staffSchedule->delete();

        return response()->json(null, 204);
    }
}
