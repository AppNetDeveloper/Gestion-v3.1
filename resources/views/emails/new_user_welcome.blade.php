<x-mail::message>
# {{ __('Welcome to :app_name, :name!', ['app_name' => config('app.name'), 'name' => $userName]) }}

{{ __('Your account has been created successfully.') }}

{{ __('Here are your login details:') }}
- **{{ __('Email') }}:** {{ $userEmail }}
- **{{ __('Password') }}:** {{ $password }}

{{ __('We recommend changing your password after your first login.') }}

<x-mail::button :url="$loginUrl">
{{ __('Login to Your Account') }}
</x-mail::button>

{{ __('If you have any questions, please contact us.') }}

{{ __('Thanks,') }}<br>
{{ config('app.name') }}
</x-mail::message>
