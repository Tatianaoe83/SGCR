<div class="space-y-6">
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Nombre del Permiso
        </label>
        <input type="text" name="name" id="name" value="{{ old('name', $permission->name ?? '') }}" required
            placeholder="ej: users.create"
            class="form-input w-full @error('name') border-red-500 @enderror">
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            Usa el formato "modulo.accion". El módulo agrupa el permiso en las pantallas de roles.
        </p>
        @error('name')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="guard_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Guard
        </label>
        <select name="guard_name" id="guard_name" required
            class="form-select w-full @error('guard_name') border-red-500 @enderror">
            <option value="web" @selected(old('guard_name', $permission->guard_name ?? 'web') === 'web')>Web (web)</option>
            <option value="api" @selected(old('guard_name', $permission->guard_name ?? 'web') === 'api')>API (api)</option>
        </select>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            Determina con qué sistema de autenticación se valida el permiso.
        </p>
        @error('guard_name')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
        <a href="{{ route('permissions.index') }}" class="btn-secondary">Cancelar</a>
        <button type="submit" class="btn-primary">{{ $textoBoton }}</button>
    </div>
</div>
