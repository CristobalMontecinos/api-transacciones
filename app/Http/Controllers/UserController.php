<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class UserController extends Controller
{
    // GET /api/users
    public function index()
    {
        try {
            $users = User::all();
            return response()->json($users, 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los usuarios',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    // POST /api/users
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6',
                'saldo_inicial' => 'required|numeric|min:0'
            ],[
                'name.required' => 'El campo nombre es requerido',
                'name.string' => 'El nombre debe ser una cadena de caracteres',
                'name.max' => 'El nombre no debe exceder los 255 caracteres',
                'email.required' => 'El campo correo electrónico es requerido', 
                'email.email' => 'El correo electrónico no es válido',
                'email.unique' => 'El correo electrónico ya está en uso',
                'password.min' => 'La contraseña debe tener al menos 6 caracteres',
                'saldo_inicial.min' => 'El saldo inicial debe ser mayor o igual a 0'
            ]);
            
            $validated['password'] = Hash::make($validated['password']);
            
            $user = User::create($validated);
            
            return response()->json([
                'message' => 'Usuario creado exitosamente',
                'data' => $user
            ], 201);
            
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al crear el usuario',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    // GET /api/users/{id}
    public function show(User $user)
    {
        try {
            return response()->json($user, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Usuario no encontrado'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al obtener el usuario',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    // PUT /api/users/{id}
    public function update(Request $request, User $user)
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
                'password' => 'sometimes|required|string|min:6',
                'saldo_inicial' => 'sometimes|required|numeric|min:0'
            ]);

            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }

            $user->update($validated);

            return response()->json([
                'message' => 'Usuario actualizado exitosamente',
                'data' => $user
            ], 200);
            
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Usuario no encontrado'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el usuario',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    // DELETE /api/users/{id}
    public function destroy(User $user)
    {
        try {
            $user->delete();
            
            return response()->json([
                'message' => 'Usuario eliminado correctamente'
            ], 200);
            
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Usuario no encontrado'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar el usuario',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }
}