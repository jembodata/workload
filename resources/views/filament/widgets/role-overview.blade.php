<x-filament-widgets::widget>
    <x-filament::section>
        @if ($roles->isEmpty())
            <div class="text-sm text-gray-500">Belum ada role untuk ditampilkan.</div>
        @else
        <div x-data="{ tab: 'role-{{ $roles->first()->id }}' }">
            <!-- Tabs Header -->
            <x-filament::tabs label="Roles">
                @foreach ($roles as $role)
                    <x-filament::tabs.item
                        @click="tab = 'role-{{ $role->id }}'"
                        x-bind:class="tab === 'role-{{ $role->id }}' 
                            ? 'bg-gray-300 text-gray-900 rounded-md' 
                            : 'bg-blue-600 text-white rounded-md'">
                        <span>{{ $role->name }}</span>
                        <span class="ml-2 text-xs rounded-full px-2 py-0.5"
                              x-bind:class="tab === 'role-{{ $role->id }}' 
                                ? 'bg-white text-gray-900' 
                                : 'bg-white text-blue-600'">
                            {{ $role->tasks_count }}
                        </span>
                    </x-filament::tabs.item>
                @endforeach
            </x-filament::tabs>

            <!-- Tabs Content -->
            <x-filament::section>
                @foreach ($roles as $role)
                    <div x-show="tab === 'role-{{ $role->id }}'">
                        {{-- Widget tabel sesuai role --}}
                        @livewire(\App\Filament\Widgets\RoleOverviewTable::class, ['roleId' => $role->id])
                    </div>
                @endforeach
            </x-filament::section>
        </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
