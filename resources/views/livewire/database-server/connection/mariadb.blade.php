@props(['form', 'isEdit' => false])

@include('livewire.database-server.connection._mysql-family-fields', ['form' => $form, 'isEdit' => $isEdit])
