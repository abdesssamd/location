<?php

namespace App\Livewire\Packs;

use App\Models\Pack;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class PackShow extends Component
{
    public Pack $pack;

    public function mount(Pack $pack): void
    {
        $this->pack = $pack->load(['category', 'items.product', 'images']);
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.packs.pack-show');
    }
}
