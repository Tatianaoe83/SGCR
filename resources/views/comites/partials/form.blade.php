@php
    $puestosSeleccionados = array_map('strval', (array) old('puestos_trabajo', $comite->puestos_trabajo ?? []));
@endphp

<div class="space-y-6">

    <!-- Nombre -->
    <div>
        <label class="block text-sm font-medium mb-2" for="nombreRelacion">Nombre del Comité</label>
        <input id="nombreRelacion" class="form-input w-full" type="text" name="nombreRelacion" maxlength="200"
            value="{{ old('nombreRelacion', $comite->nombreRelacion ?? '') }}" required />
        @error('nombreRelacion')
        <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
        @enderror
    </div>

    <!-- Elemento -->
    <div>
        <label class="block text-sm font-medium mb-2" for="elementoID">Elemento</label>
        <select id="elementoID" class="select2 form-select w-full" name="elementoID" data-placeholder="Seleccionar Elemento" required>
            <option value="">Seleccionar Elemento</option>
            @foreach($elementos as $elemento)
            <option value="{{ $elemento->id_elemento }}" @selected(old('elementoID', $comite->elementoID ?? null) == $elemento->id_elemento)>
                {{ $elemento->folio_elemento ? $elemento->folio_elemento . ' - ' : '' }}{{ $elemento->nombre_elemento }}
            </option>
            @endforeach
        </select>
        <p class="text-xs text-slate-500 mt-1">Elemento del SGC al que pertenece el comité.</p>
        @error('elementoID')
        <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
        @enderror
    </div>

    <!-- Puestos relacionados -->
    <div>
        <label class="block text-sm font-medium mb-2" for="puestos_trabajo">Puestos Relacionados</label>
        <select id="puestos_trabajo" class="select2 form-select w-full" name="puestos_trabajo[]" multiple data-placeholder="Seleccionar Puestos" required>
            @foreach($puestosTrabajo as $puesto)
            <option value="{{ $puesto->id_puesto_trabajo }}" @selected(in_array((string) $puesto->id_puesto_trabajo, $puestosSeleccionados, true))>
                {{ $puesto->nombre }}
            </option>
            @endforeach
        </select>
        <p class="text-xs text-slate-500 mt-1">Puedes seleccionar varios puestos de trabajo.</p>
        @error('puestos_trabajo')
        <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
        @enderror
        @error('puestos_trabajo.*')
        <div class="text-red-500 text-sm mt-1">{{ $message }}</div>
        @enderror
    </div>

    <!-- Actions -->
    <div class="flex items-center justify-end space-x-3">
        <a href="{{ route('comites.index') }}" class="btn-secondary">
            Cancelar
        </a>
        <button type="submit" class="btn-primary">
            {{ $submitLabel }}
        </button>
    </div>
</div>
