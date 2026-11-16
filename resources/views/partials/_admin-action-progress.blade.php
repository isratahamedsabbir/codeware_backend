{{-- The thin top progress bar wire:navigate already shows between pages, for the
     Livewire actions that load something in place without navigating (e.g. Edit
     on a table that sits beside its form). Livewire's own bar is internal to
     its navigate plugin, so this draws the same one (same colour variable, same
     2px strip) around the named actions. Add an action name to $actions to get
     the bar for it too. --}}
<script>
    (() => {
        const actions = ['edit'];
        let bar = null, timer = null, running = 0;

        const start = () => {
            if (running++ > 0) return;
            clearInterval(timer);
            bar?.remove();
            bar = document.createElement('div');
            bar.style.cssText = 'position:fixed;top:0;left:0;height:2px;width:0;z-index:1031;pointer-events:none;'
                + 'background:var(--livewire-progress-bar-color,#29d);box-shadow:0 0 10px var(--livewire-progress-bar-color,#29d);'
                + 'transition:width .2s ease,opacity .3s ease';
            document.body.appendChild(bar);
            let w = 10;
            requestAnimationFrame(() => { if (bar) bar.style.width = w + '%'; });
            timer = setInterval(() => {
                w += (90 - w) * 0.15;
                if (bar) bar.style.width = w + '%';
            }, 200);
        };

        const finish = () => {
            if (--running > 0) return;
            running = 0;
            clearInterval(timer);
            const el = bar;
            if (!el) return;
            el.style.width = '100%';
            setTimeout(() => { el.style.opacity = '0'; }, 200);
            setTimeout(() => { el.remove(); if (bar === el) bar = null; }, 500);
        };

        // This script re-runs on every wire:navigate page swap, but livewire:init
        // only fires on a full page load — so register right away when Livewire
        // is already up, and only once.
        const register = () => {
            if (window.__adminActionProgress) return;
            window.__adminActionProgress = true;
            Livewire.interceptAction(({ action, onFinish }) => {
                if (! actions.includes(action.name)) return;
                start();
                onFinish(finish);
            });
        };

        if (window.Livewire?.interceptAction) register();
        else document.addEventListener('livewire:init', register);
    })();
</script>
