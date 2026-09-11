@php
    $seleccionados = collect(old('permissions', $seleccionados ?? []))->map(fn($id) => (int) $id)->all();

    $etiquetasGrupo = [
        'divisions' => 'Divisiones',
        'unidades-negocios' => 'Unidades de negocio',
        'areas' => 'Áreas',
        'puestos-trabajo' => 'Puestos de trabajo',
        'empleados' => 'Empleados',
        'users' => 'Usuarios',
        'comites' => 'Comités',
        'matriz' => 'Matriz de responsabilidades',
        'roles' => 'Roles',
        'permissions' => 'Permisos',
        'sgc' => 'Sección SGC',
        'tipo-elemento' => 'Tipos de elemento',
        'tipo-proceso' => 'Tipos de proceso',
        'elementos' => 'Elementos',
        'cuerpo-correo' => 'Cuerpos de correo',
        'control-cambios' => 'Control de cambios',
        'propuesta_mejora' => 'Propuestas de mejora',
        'dashboard' => 'Dashboard',
    ];

    $etiquetasAccion = [
        'view' => 'Ver',
        'create' => 'Crear',
        'edit' => 'Editar',
        'delete' => 'Eliminar',
        'destroy' => 'Eliminar',
        'import' => 'Importar',
        'export' => 'Exportar',
        'info' => 'Ver información',
        'acceso' => 'Acceso',
        'access' => 'Acceso',
        'send-credentials' => 'Enviar credenciales',
    ];

    $secciones = [
        'Estructura de la empresa' => ['divisions', 'unidades-negocios', 'areas'],
        'Usuarios' => ['puestos-trabajo', 'empleados', 'users', 'comites', 'matriz', 'roles', 'permissions'],
        'Sistema de gestión de calidad' => ['sgc', 'tipo-elemento', 'tipo-proceso', 'elementos', 'cuerpo-correo', 'control-cambios', 'propuesta_mejora'],
        'General' => ['dashboard'],
    ];

    $iconoGrupo = [
        'divisions' => 'building', 'unidades-negocios' => 'building', 'areas' => 'building',
        'puestos-trabajo' => 'briefcase', 'empleados' => 'users', 'users' => 'users', 'comites' => 'users',
        'matriz' => 'grid', 'roles' => 'shield', 'permissions' => 'shield',
        'cuerpo-correo' => 'mail', 'dashboard' => 'grid',
    ];

    $iconos = [
        'building' => 'M3 21V5l6-2 6 2 6-2v16M3 21h18M9 3v18M15 5v16',
        'users' => 'M9 11.2a3.2 3.2 0 100-6.4 3.2 3.2 0 000 6.4zM3.5 19a5.5 5.5 0 0111 0M16 6.5a3 3 0 010 6M20.5 19a5 5 0 00-3.5-4.8',
        'briefcase' => 'M4 8h16v11H4zM9 8V5h6v3M4 13h16',
        'shield' => 'M12 3l7 3v5c0 4.5-3 8.3-7 10-4-1.7-7-5.5-7-10V6l7-3zM9 12l2 2 4-4',
        'grid' => 'M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z',
        'mail' => 'M4 6h16v12H4zM4 7l8 6 8-6',
        'doc' => 'M7 3h7l5 5v13H7zM14 3v5h5M10 13h6M10 17h6',
    ];

    $grupos = $permissions->groupBy(fn($permission) => \Illuminate\Support\Str::before($permission->name, '.'));

    $conocidos = collect($secciones)->flatten()->all();
    $otros = $grupos->keys()->reject(fn($grupo) => in_array($grupo, $conocidos, true))->sort()->values()->all();
    if ($otros) {
        $secciones['Otros'] = $otros;
    }

    $secciones = collect($secciones)
        ->map(fn($claves) => collect($claves)->filter(fn($grupo) => $grupos->has($grupo))->values())
        ->filter(fn($claves) => $claves->isNotEmpty());

    $etiquetaGrupo = fn($grupo) => $etiquetasGrupo[$grupo] ?? ucfirst(str_replace(['-', '_'], ' ', $grupo));
    $etiquetaAccion = fn($nombre) => $etiquetasAccion[\Illuminate\Support\Str::after($nombre, '.')]
        ?? ucfirst(str_replace(['-', '_'], ' ', \Illuminate\Support\Str::after($nombre, '.')));
@endphp

<style>
    .perm {
        --perm-accent: var(--navy);
        --perm-accent-soft: rgba(14, 29, 73, .06);
        --perm-accent-ring: rgba(14, 29, 73, .22);
    }
    .dark .perm {
        --perm-accent: var(--accent);
        --perm-accent-soft: rgba(90, 134, 222, .14);
        --perm-accent-ring: rgba(90, 134, 222, .35);
    }
    .perm-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem; }
    .perm-head-title { font-size: .95rem; font-weight: 600; color: var(--text); }
    .perm-section-title { margin-bottom: .5rem; }
    .perm-section-toggle {
        display: flex; align-items: center; gap: .75rem; width: 100%; min-height: 32px; padding: 0;
        background: none; border: 0; cursor: pointer; text-align: left;
        font-size: .75rem; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--text-2);
        transition: color .15s;
    }
    .perm-section-toggle:hover { color: var(--perm-accent); }
    .perm-section-toggle:focus-visible { outline: none; box-shadow: 0 0 0 3px var(--perm-accent-ring); border-radius: 6px; }
    .perm-section-line { flex: 1; height: 1px; background: var(--border); }
    .perm-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: .75rem; align-items: start; }
    @media (min-width: 768px) {
        .perm-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    .perm-card {
        border: 1px solid var(--border); border-radius: var(--radius); background: var(--surface);
        overflow: hidden; transition: border-color .2s, box-shadow .2s;
    }
    .perm-card.is-full { border-color: var(--perm-accent); box-shadow: 0 0 0 1px var(--perm-accent-ring); }
    .perm-card-head { display: flex; align-items: center; gap: .625rem; padding: .625rem .75rem; }
    .perm-icon {
        flex: none; display: grid; place-items: center; width: 2rem; height: 2rem; border-radius: var(--radius-sm);
        background: var(--perm-accent-soft); color: var(--perm-accent);
    }
    .perm-icon svg { width: 1rem; height: 1rem; }
    .perm-card-title { flex: 1; min-width: 0; font-size: .9rem; font-weight: 600; color: var(--text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .perm-chev { flex: none; width: 1rem; height: 1rem; transition: transform .2s ease-out; }
    .perm-master { display: flex; align-items: center; gap: .375rem; font-size: .75rem; color: var(--text-2); cursor: pointer; white-space: nowrap; }
    .perm-master input { width: 1.125rem; height: 1.125rem; accent-color: var(--perm-accent); cursor: pointer; }
    .perm-chips { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .375rem; padding: .625rem; border-top: 1px solid var(--border); }
    .perm-chip {
        position: relative; display: flex; align-items: center; gap: .5rem; min-height: 36px; padding: .375rem .625rem;
        border: 1px solid var(--border); border-radius: var(--radius-sm); background: var(--surface);
        cursor: pointer; user-select: none; transition: border-color .15s, background-color .15s;
    }
    .perm-chip:hover { border-color: var(--perm-accent); }
    .perm-chip:has(input:checked) { border-color: var(--perm-accent); background: var(--perm-accent-soft); }
    .perm-chip:has(input:focus-visible) { box-shadow: 0 0 0 3px var(--perm-accent-ring); }
    .perm-chip input { position: absolute; opacity: 0; width: 1px; height: 1px; pointer-events: none; }
    .perm-box {
        flex: none; display: grid; place-items: center; width: 1.125rem; height: 1.125rem; border-radius: 5px;
        border: 1.5px solid var(--text-3); color: transparent; transition: background-color .15s, border-color .15s;
    }
    .perm-box svg { width: .75rem; height: .75rem; }
    .perm-chip:has(input:checked) .perm-box { background: var(--perm-accent); border-color: var(--perm-accent); color: #fff; }
    .perm-chip-label { min-width: 0; font-size: .8125rem; font-weight: 500; line-height: 1.25; color: var(--text); }
    @media (prefers-reduced-motion: reduce) {
        .perm *, .perm *::before, .perm *::after { transition: none !important; }
    }
</style>

<div class="space-y-6">
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Nombre del Rol
        </label>
        <input type="text" name="name" id="name" value="{{ old('name', $role->name ?? '') }}" required
            placeholder="ej: Coordinador de Calidad"
            class="form-input w-full @error('name') border-red-500 @enderror">
        @error('name')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    @if($permissions->count() > 0)
    <div class="perm space-y-4"
        x-data="{
            conteo: {},
            recalcular() {
                const conteo = {};
                this.$el.querySelectorAll('input[name=\'permissions[]\']').forEach(el => {
                    conteo[el.dataset.grupo] = (conteo[el.dataset.grupo] || 0) + (el.checked ? 1 : 0);
                });
                this.conteo = conteo;
            },
            marcar(selector, valor) {
                this.$el.querySelectorAll(selector).forEach(el => el.checked = valor);
                this.recalcular();
            }
        }"
        x-init="recalcular()"
        @change="recalcular()">

        <div class="perm-head">
            <span class="perm-head-title">Permisos del rol</span>
            <div class="flex flex-wrap gap-2">
                <button type="button" class="btn-secondary" @click="marcar('input[name=\'permissions[]\']', true)">Seleccionar todo</button>
                <button type="button" class="btn-secondary" @click="marcar('input[name=\'permissions[]\']', false)">Limpiar</button>
            </div>
        </div>

        @foreach($secciones as $seccion => $clavesGrupo)
        <section x-data="{ abierto: true }">
            <h3 class="perm-section-title">
                <button type="button" class="perm-section-toggle" @click="abierto = !abierto"
                    :aria-expanded="abierto.toString()" aria-controls="perm-seccion-{{ $loop->index }}">
                    <span>{{ $seccion }}</span>
                    <span class="perm-section-line" aria-hidden="true"></span>
                    <svg class="perm-chev" :style="abierto ? 'transform: rotate(180deg)' : ''" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </h3>

            <div id="perm-seccion-{{ $loop->index }}" x-show="abierto" x-collapse>
            <div class="perm-grid">
                @foreach($clavesGrupo as $grupo)
                @php
                    $permisosGrupo = $grupos[$grupo];
                    $totalGrupo = $permisosGrupo->count();
                    $nombreGrupo = $etiquetaGrupo($grupo);
                    $icono = $iconos[$iconoGrupo[$grupo] ?? 'doc'];
                @endphp
                <div class="perm-card"
                    :class="{ 'is-full': (conteo['{{ $grupo }}'] || 0) === {{ $totalGrupo }} }">

                    <div class="perm-card-head">
                        <span class="perm-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $icono }}"/></svg>
                        </span>

                        <span class="perm-card-title" title="{{ $nombreGrupo }}">{{ $nombreGrupo }}</span>

                        <label class="perm-master">
                            <input type="checkbox"
                                aria-label="Seleccionar todos los permisos de {{ $nombreGrupo }}"
                                :checked="(conteo['{{ $grupo }}'] || 0) === {{ $totalGrupo }}"
                                x-effect="$el.indeterminate = (conteo['{{ $grupo }}'] || 0) > 0 && (conteo['{{ $grupo }}'] || 0) < {{ $totalGrupo }}"
                                @change.stop="marcar('input[data-grupo=\'{{ $grupo }}\']', $event.target.checked)">
                            <span>Todos</span>
                        </label>
                    </div>

                    <div class="perm-chips">
                        @foreach($permisosGrupo as $permission)
                        <label for="permission_{{ $permission->id }}" class="perm-chip" title="{{ $permission->name }}">
                            <input type="checkbox" name="permissions[]" id="permission_{{ $permission->id }}"
                                value="{{ $permission->id }}"
                                data-grupo="{{ $grupo }}"
                                @checked(in_array($permission->id, $seleccionados))>
                            <span class="perm-box" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <span class="perm-chip-label">{{ $etiquetaAccion($permission->name) }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
            </div>
        </section>
        @endforeach

        @error('permissions')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>
    @else
    <div class="text-center py-8 border border-dashed border-gray-300 dark:border-gray-600 rounded-lg">
        <p class="text-sm text-gray-500 dark:text-gray-400">No hay permisos disponibles.</p>
        @can('permissions.create')
        <a href="{{ route('permissions.create') }}" class="btn-primary mt-3">Crear Permisos</a>
        @endcan
    </div>
    @endif

    <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
        <a href="{{ route('roles.index') }}" class="btn-secondary">Cancelar</a>
        <button type="submit" class="btn-primary">{{ $textoBoton }}</button>
    </div>
</div>
