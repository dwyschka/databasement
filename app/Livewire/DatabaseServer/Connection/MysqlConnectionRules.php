<?php

namespace App\Livewire\DatabaseServer\Connection;

use App\Livewire\DatabaseServer\Form;

class MysqlConnectionRules extends ClientServerConnectionRules
{
    public function rules(Form $form): array
    {
        return array_merge(parent::rules($form), [
            'phpmyadmin_enabled' => 'boolean',
            'phpmyadmin_url' => [
                $form->phpmyadmin_enabled ? 'required' : 'nullable',
                'string', 'url', 'max:500',
            ],
        ]);
    }

    public function extraConfig(Form $form): array
    {
        $extra = $form->ssl_enabled ? ['ssl_enabled' => true] : [];

        if ($form->phpmyadmin_enabled && $form->phpmyadmin_url !== '') {
            $extra['phpmyadmin_enabled'] = true;
            $extra['phpmyadmin_url'] = $form->phpmyadmin_url;
        }

        return $extra;
    }

    public function dumpPreviewConfig(Form $form): array
    {
        return ['ssl_enabled' => $form->ssl_enabled];
    }
}
