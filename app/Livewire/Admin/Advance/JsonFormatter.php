<?php

namespace App\Livewire\Admin\Advance;

use Livewire\Component;

/**
 * Formatting happens entirely in the browser (Alpine) — the pasted JSON never
 * leaves the page, so this component only supplies the layout.
 */
class JsonFormatter extends Component
{
    public function render()
    {
        return view('livewire.admin.advance.json-formatter')->layout('layouts.admin', ['title' => 'JSON Formatter']);
    }
}
