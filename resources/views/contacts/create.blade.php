<x-app-layout>
    @php
        // Definimos $contact como null para evitar errores si se referencia en la vista.
        $contact = null;
    @endphp

    <div class="mb-6">
        <x-breadcrumb 
            :breadcrumb-items="[
                ['name' => 'Contacts', 'url' => route('contacts.index'), 'active' => false],
                ['name' => 'Add New Contact', 'url' => '', 'active' => true]
            ]" 
            :page-title="'Add New Contact'" 
        />
    </div>

    @if($errors->any())
        <div class="mb-4">
            <ul class="text-red-600">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow rounded-lg p-6">
        <form action="{{ route('contacts.store') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block mb-1">Name</label>
                <input type="text" name="name" class="w-full p-2 border rounded" value="{{ old('name', $contact->name ?? '') }}" required>
            </div>
            <div class="mb-4">
                <label class="block mb-1">Phone</label>
                <input type="text" name="phone" class="w-full p-2 border rounded" value="{{ old('phone', $contact->phone ?? '') }}" required>
            </div>
            <div class="mb-4">
                <label class="block mb-1">Address</label>
                <input type="text" name="address" class="w-full p-2 border rounded" value="{{ old('address', $contact->address ?? '') }}">
            </div>
            <div class="mb-4">
                <label class="block mb-1">Email</label>
                <input type="email" name="email" class="w-full p-2 border rounded" value="{{ old('email', $contact->email ?? '') }}">
            </div>
            <div class="mb-4">
                <label class="block mb-1">Web</label>
                <input type="url" name="web" class="w-full p-2 border rounded" value="{{ old('web', $contact->web ?? '') }}">
            </div>
            <div class="mb-4">
                <label class="block mb-1">Telegram (Peer/ID)</label>
                <input type="text" name="telegram" class="w-full p-2 border rounded" value="{{ old('telegram', $contact->telegram ?? '') }}">
            </div>
            <button type="submit" class="btn btn-primary">Save Contact</button>
        </form>
    </div>
</x-app-layout>

