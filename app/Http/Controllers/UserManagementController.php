<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\AccesoMail;

class UserManagementController extends Controller
{
    public function index()
    {
        $users = User::with('roles')->get();
        return view('users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::all();
        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'roles' => 'array',
            'roles.*' => 'exists:roles,id'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'temp_password' => $request->password, // Guardar contraseña temporal
        ]);

        if ($request->has('roles')) {
            $roles = Role::whereIn('id', $request->roles)->get();
            $user->syncRoles($roles);
        }

        return redirect()->route('users.index')->with('success', 'Usuario creado exitosamente.');
    }

    public function show(User $user)
    {
        $user->load('roles');
        return view('users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        $user->load('roles');
        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'roles' => 'array',
            'roles.*' => 'exists:roles,id'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        if ($request->filled('password')) {
            $user->update([
                'password' => Hash::make($request->password),
                'temp_password' => $request->password, // Actualizar contraseña temporal
            ]);
        }

        if ($request->has('roles')) {
            $roles = Role::whereIn('id', $request->roles)->get();
            $user->syncRoles($roles);
        } else {
            $user->syncRoles([]);
        }

        return redirect()->route('users.index')->with('success', 'Usuario actualizado exitosamente.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        $user->delete();
        return redirect()->route('users.index')->with('success', 'Usuario eliminado exitosamente.');
    }

    public function sendCredentials(User $user)
    {
        // Usar la contraseña temporal guardada, o generar una nueva si no existe
        $password = $user->temp_password;
        $usingExistingPassword = true;
        
        if (!$password) {
            // Si no hay contraseña temporal guardada, generar una nueva
            $password = \Illuminate\Support\Str::random(12);
            $usingExistingPassword = false;
            
            // Actualizar tanto la contraseña hasheada como la temporal
            $user->update([
                'password' => Hash::make($password),
                'temp_password' => $password
            ]);
        }

        try {
            // Enviar correo con credenciales
            Mail::to($user->email)->send(new AccesoMail($user, $password));
            
            $message = $usingExistingPassword
                ? 'Credenciales enviadas exitosamente por correo electrónico (contraseña actual del sistema).'
                : 'Credenciales enviadas exitosamente por correo electrónico (nueva contraseña generada porque no se encontró contraseña anterior).';
            
            return redirect()->route('users.index')->with('success', $message);
        } catch (\Exception $e) {
            \Log::error('Error al enviar correo de credenciales', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return redirect()->route('users.index')->with('error', 'Error al enviar el correo: ' . $e->getMessage());
        }
    }
}
