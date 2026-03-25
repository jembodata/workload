<x-filament-panels::page>

    <x-filament::section>
        <x-slot name="heading">
            Gantt & Recources
        </x-slot>

        <x-slot name="description">
            View only
        </x-slot>

        <div x-data="{tab: 'tab1'}">
            <x-filament::tabs label="Content tabs" contained="false">
                <x-filament::tabs.item @click="tab = 'tab1'" :alpine-active="'tab === \'tab1\''">
                    Gantt Chart
                </x-filament::tabs.item>

                <x-filament::tabs.item @click="tab = 'tab2'" :alpine-active="'tab === \'tab2\''">
                    Resources
                </x-filament::tabs.item>

                <x-filament::tabs.item @click="tab = 'tab3'" :alpine-active="'tab === \'tab3\''">
                    Analitik
                </x-filament::tabs.item>
            </x-filament::tabs>

            <x-filament::section>
                <div>
                    <div x-show="tab === 'tab1'">
                        <livewire:show-gantt />
                    </div>

                    <div x-show="tab === 'tab2'">
                        <livewire:show-resources />
                    </div>

                    <div x-show="tab === 'tab3'">
                        <livewire:show-analytics />
                    </div>
                </div>
            </x-filament::section>
        </div>
    </x-filament::section>

        {{-- Footer --}}
        <footer class="text-center py-4 text-xs text-gray-400">
            © {{ date('Y') }} Ryan. All rights reserved.
        </footer>

    @stack('styles')
    @stack('scripts')

</x-filament-panels::page>
