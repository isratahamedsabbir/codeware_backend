<?php

namespace App\Livewire\Frontend;

use App\Support\Builder\DynamicBlocks;
use Livewire\Attributes\Lazy;
use Livewire\Component;

/** One dynamic block (products, posts, services, brands) of a page-builder design, loaded lazily. */
#[Lazy]
class DynamicBlock extends Component
{
    public string $type = '';

    /** @var array<string, string> the placeholder's data-* options */
    public array $options = [];

    public bool $withStyle = true;

    public function placeholder(): string
    {
        return '<div style="text-align:center;color:#94a3b8;padding:40px 20px">Loading…</div>';
    }

    public function render()
    {
        return view('livewire.frontend.dynamic-block', [
            'html' => DynamicBlocks::block($this->type, $this->options, $this->withStyle),
        ]);
    }
}
