@props(['form', 'isEdit' => false])

@include('livewire.database-server.connection._client-server-fields', ['form' => $form, 'isEdit' => $isEdit])

<x-checkbox
    wire:model.live="form.ssl_enabled"
    :label="__('Use SSL')"
    :hint="__('Required for servers that enforce TLS, such as Amazon RDS with require_secure_transport. The server certificate is not verified.')"
/>

<x-checkbox
    wire:model.live="form.phpmyadmin_enabled"
    :label="__('Open in phpMyAdmin')"
    :hint="__('Adds an \"Open in phpMyAdmin\" browse action that links to an existing, externally hosted phpMyAdmin instance with host, port and username pre-filled. Databasement does not host phpMyAdmin itself — the password is shown separately to paste in, since phpMyAdmin does not support passing it via URL.')"
/>

@if ($form->phpmyadmin_enabled)
    <x-input
        wire:model.blur="form.phpmyadmin_url"
        :label="__('phpMyAdmin URL')"
        placeholder="https://panel.example.com/phpmyadmin/"
        :hint="__('Base URL of the phpMyAdmin instance to open, e.g. from your hosting provider\'s control panel.')"
    />
@endif
