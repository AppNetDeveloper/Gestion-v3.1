@props([
    'name',
    'options'       => [],   // ['id'=>'Label', …]  o  colección de objetos
    'selected'      => null,
    'placeholder'   => null,
])

<select {{ $attributes->merge([
            'name' => $name,
            'id'   => $id ?? $name,
            'class'=> 'inputField select2 w-full p-3 border rounded-md'
        ]) }}>
    @if($placeholder)
        <option value="" disabled {{ $selected ? '' : 'selected' }}>{{ $placeholder }}</option>
    @endif

    @foreach($options as $value => $label)
        {{-- si $options es colección, adapta aquí --}}
        <option value="{{ $value }}" {{ (string)$selected===(string)$value ? 'selected' : '' }}>
            {{ is_object($label) ? $label->name : $label }}
        </option>
    @endforeach
</select>
