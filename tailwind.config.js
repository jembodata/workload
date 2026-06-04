import preset from './vendor/filament/support/tailwind.config.preset.js'

export default {
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './app/Livewire/**/*.php',
        './resources/views/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
        './vendor/guava/filament-modal-relation-managers/resources/**/*.blade.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                // sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                sans: ['Figtree', 'ui-sans-serif', 'system-ui', 'sans-serif'],
            },
        },
    },
}
