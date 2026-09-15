<?php

namespace App\Livewire\DatabaseServer;

use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class PhpMyAdminModal extends Component
{
    public bool $showModal = false;

    public string $serverName = '';

    public string $databaseIcon = '';

    public string $databaseType = '';

    #[Locked]
    public string $phpmyadminUrl = '';

    public string $username = '';

    public string $password = '';

    #[On('open-phpmyadmin-modal')]
    public function openModal(
        string $serverName,
        string $databaseIcon,
        string $databaseType,
        string $phpmyadminUrl,
        string $username,
        string $password,
    ): void {
        $this->serverName = $serverName;
        $this->databaseIcon = $databaseIcon;
        $this->databaseType = $databaseType;
        $this->phpmyadminUrl = $phpmyadminUrl;
        $this->username = $username;
        $this->password = $password;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->phpmyadminUrl = '';
        $this->username = '';
        $this->password = '';
    }

    public function render(): View
    {
        return view('livewire.database-server.phpmyadmin-modal');
    }
}
