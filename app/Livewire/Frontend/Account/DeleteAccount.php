<?php

namespace App\Livewire\Frontend\Account;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class DeleteAccount extends Component
{
    public string $current_password = '';

    public bool $confirm_deletion = false;

    public function delete(): void
    {
        $this->validate([
            'current_password' => ['required', 'current_password:web'],
            'confirm_deletion' => ['accepted'],
        ]);

        $user = Auth::user();

        // The primary admin is protected by the admin user manager too.
        if ($user->id === 1) {
            $this->addError('current_password', __('The primary admin account cannot be deleted.'));

            return;
        }

        $photo = $user->photo;

        DB::transaction(function () use ($user): void {
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $user->tokens()->delete();
            $user->delete();
        });

        if ($photo) {
            Storage::disk('public')->delete($photo);
        }

        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        $this->redirect(route('home'), navigate: false);
    }

    public function render()
    {
        return view('livewire.frontend.account-delete');
    }
}
