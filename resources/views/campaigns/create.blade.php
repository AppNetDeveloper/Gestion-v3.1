<x-app-layout>
    <div class="container mx-auto py-8">
        <h1 class="text-2xl font-bold mb-4">Create New Campaign</h1>

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 p-4 rounded mb-4">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('campaigns.store') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label for="prompt" class="block text-gray-700">Prompt</label>
                <textarea name="prompt" id="prompt" rows="4" class="w-full border rounded p-2">{{ old('prompt') }}</textarea>
            </div>

            <div class="mb-4">
                <label for="campaign_start" class="block text-gray-700">Campaign Start (Date and Time)</label>
                <input type="datetime-local" name="campaign_start" id="campaign_start" class="w-full border rounded p-2" value="{{ old('campaign_start') }}">
            </div>

            <div class="mb-4">
                <label for="model" class="block text-gray-700">Model</label>
                <select name="model" id="model" class="w-full border rounded p-2">
                    <option value="whatsapp" {{ old('model') == 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                    <option value="email" {{ old('model') == 'email' ? 'selected' : '' }}>Email</option>
                    <option value="sms" {{ old('model') == 'sms' ? 'selected' : '' }}>SMS</option>
                    <option value="telegram" {{ old('model') == 'telegram' ? 'selected' : '' }}>Telegram</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">{{ __('Create Campaign') }}</button>
        </form>
    </div>
</x-app-layout>
