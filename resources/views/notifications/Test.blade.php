@if ($setupCompleted = \App\Models\Setting::setupCompleted())
@component('mail::message')
@endif

{{ trans('mail.test_mail_text') }}

{{ trans('mail.thanks') }}

{{ $snipeSettings->site_name }}
@if ($setupCompleted)
@endcomponent
@endif
