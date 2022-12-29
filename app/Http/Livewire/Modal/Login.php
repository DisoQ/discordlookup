<?php

namespace App\Http\Livewire\Modal;

use Livewire\Component;

class Login extends Component
{
    public $joinDiscord = false;

    public function mount()
    {
        if (session()->exists('joinDiscordAfterLogin')) {
            $this->joinDiscord = session()->get('joinDiscordAfterLogin');
        }else{
            $this->setJoinDiscord();
        }
    }

    public function setJoinDiscord()
    {
        if ($this->joinDiscord){
            session()->put('joinDiscordAfterLogin', true);
        }else{
            session()->put('joinDiscordAfterLogin', false);
        }
    }

    public function updated($propertyName)
    {
        if ($propertyName == 'joinDiscord'){
            $this->setJoinDiscord();
        }
    }

    public function render()
    {
        return view('modal.login');
    }
}
