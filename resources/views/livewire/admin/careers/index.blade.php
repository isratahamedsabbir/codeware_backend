<div>
    <div class="mb-3 flex items-center justify-between gap-4 flex-wrap">
        <div>
            @include('partials._admin-breadcrumbs', ['routeName' => 'admin.careers'])
        </div>
        <div class="page-header-actions flex items-center gap-2 shrink-0">
            <flux:button variant="ghost" size="sm" icon="plus" wire:click="create">New Job Post</flux:button>
        </div>
    </div>

    <div class="bg-white rounded-[5px] shadow-sm overflow-hidden">
        <div class="flex items-center gap-3 p-4 flex-wrap">
            <x-per-page-select :options="$this->perPageOptions()" />
            <select wire:model.live="statusFilter"
                class="px-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 bg-white appearance-none pr-8 min-w-[140px] transition-all"
                style="background-image:url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%23aaa' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E&quot;);background-repeat:no-repeat;background-position:right 10px center">
                <option value="">All statuses</option>
                <option value="draft">Draft</option>
                <option value="open">Open</option>
                <option value="closed">Closed</option>
            </select>
            <div class="relative max-w-xs ml-auto">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-600" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8" />
                    <line x1="21" y1="21" x2="16.65" y2="16.65" />
                </svg>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by title…"
                    class="w-full pl-9 pr-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all" />
            </div>
        </div>

        <div class="overflow-x-auto">
            <div class="border border-zinc-100 rounded-lg">
                <table class="w-full divide-y divide-gray-200">
                    <colgroup>
                        <col style="width:32%">
                        <col class="hidden lg:table-column" style="width:15%">
                        <col class="hidden lg:table-column" style="width:13%">
                        <col style="width:10%">
                        <col style="width:12%">
                        <col style="width:18%">
                    </colgroup>
                    <thead>
                        <tr class="bg-zinc-50">
                            <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Title</th>
                            <th class="hidden lg:table-cell px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Department</th>
                            <th class="hidden lg:table-cell px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Deadline</th>
                            <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Applicants</th>
                            <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Status</th>
                            <th class="sticky right-0 z-10 bg-zinc-50 border-l border-zinc-100 px-4 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($jobs as $job)
                            <tr wire:key="job-{{ $job->id }}"
                                class="group/row hover:bg-indigo-50/30 transition-colors"
                                @contextmenu.prevent="$el.querySelector('[data-actions-trigger]')?.click()">
                                <td class="px-4 py-2">
                                    <div class="font-medium text-zinc-900 text-sm">{{ $job->title }}</div>
                                    <div class="text-xs text-zinc-500">{{ $job->employmentTypeLabel() }}@if ($job->location) · {{ $job->location }}@endif</div>
                                </td>
                                <td class="hidden lg:table-cell px-4 py-2 text-sm text-zinc-600">{{ $job->department ?: '—' }}</td>
                                <td class="hidden lg:table-cell px-4 py-2 text-sm text-zinc-500 whitespace-nowrap">{{ $job->deadline?->toFormattedDateString() ?? '—' }}</td>
                                <td class="px-4 py-2 text-sm">
                                    <a href="{{ route('admin.career-applications', ['job' => $job->id]) }}" class="text-indigo-600 hover:underline">{{ $job->applications_count }}</a>
                                </td>
                                <td class="px-4 py-2">
                                    @php($badge = ['open' => 'bg-green-50 text-green-600 border-green-200', 'draft' => 'bg-zinc-50 text-zinc-600 border-zinc-200', 'closed' => 'bg-red-50 text-red-600 border-red-200'][$job->status])
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium border {{ $badge }}">{{ ucfirst($job->status) }}</span>
                                </td>
                                <td class="sticky right-0 z-10 bg-white group-hover/row:bg-indigo-100 border-l border-zinc-100 px-4 py-2">
                                    <x-admin-row-actions :actions="[
                                        ['wireClick' => 'edit(' . $job->id . ')', 'icon' => 'pencil', 'label' => 'Edit', 'color' => 'primary'],
                                        ['wireClick' => 'confirmDelete(' . $job->id . ')', 'icon' => 'trash', 'label' => 'Delete', 'color' => 'rose-500'],
                                    ]" />
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-16 text-center text-sm text-zinc-600">No job posts yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="px-6 py-3">{{ $jobs->links() }}</div>
    </div>

    {{-- Create / edit --}}
    <flux:modal name="job-form" class="md:w-[40rem]"
        x-on:open-modal.window="if ($event.detail.name === 'job-form') $flux.modal('job-form').show()"
        x-on:close-modal.window="if ($event.detail.name === 'job-form') $flux.modal('job-form').close()">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? 'Edit job post' : 'New job post' }}</flux:heading>

            <flux:input wire:model="title" label="Job title" placeholder="e.g. Senior Laravel Developer" />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <flux:input wire:model="department" label="Department" placeholder="Engineering" />
                <flux:input wire:model="location" label="Location" placeholder="Dhaka, Bangladesh" />
                <flux:select wire:model="employment_type" label="Employment type">
                    @foreach (\App\Models\JobPost::EMPLOYMENT_TYPES as $value => $label)
                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="vacancy" type="number" min="1" label="Vacancies" />
                <flux:input wire:model="deadline" type="date" label="Application deadline" />
                <flux:select wire:model="status" label="Status">
                    <flux:select.option value="draft">Draft (hidden)</flux:select.option>
                    <flux:select.option value="open">Open (visible, accepting applications)</flux:select.option>
                    <flux:select.option value="closed">Closed</flux:select.option>
                </flux:select>
            </div>

            <flux:textarea wire:model="description" label="Description" rows="5" />
            <flux:textarea wire:model="requirements" label="Requirements" rows="4" />

            <div class="flex gap-2 pt-1">
                <flux:button type="submit" variant="primary" size="sm">Save</flux:button>
                <flux:modal.close><flux:button size="sm" variant="ghost">Cancel</flux:button></flux:modal.close>
            </div>
        </form>
    </flux:modal>

    {{-- Delete --}}
    <flux:modal name="job-delete" class="md:w-80"
        x-on:open-modal.window="if ($event.detail.name === 'job-delete') $flux.modal('job-delete').show()"
        x-on:close-modal.window="if ($event.detail.name === 'job-delete') $flux.modal('job-delete').close()">
        <div class="space-y-4">
            <flux:heading>Delete job post?</flux:heading>
            <flux:text class="text-sm text-zinc-500">The post is removed from the Careers page. Existing applications are kept.</flux:text>
            <div class="flex gap-2 pt-1">
                <button wire:click="delete"
                    class="inline-flex items-center gap-2 px-4 h-8 text-sm font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 transition-colors border-none cursor-pointer">Delete</button>
                <flux:modal.close><flux:button size="sm" variant="ghost">Cancel</flux:button></flux:modal.close>
            </div>
        </div>
    </flux:modal>
</div>
