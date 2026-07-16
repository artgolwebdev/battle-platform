/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import './bootstrap';
import { createApp } from 'vue';

/**
 * Next, we will create a fresh Vue application instance. You may then begin
 * registering components with the application instance so they are ready
 * to use in your application's views. An example is included for you.
 */

const app = createApp({});

import ExampleComponent from './components/ExampleComponent.vue';
app.component('example-component', ExampleComponent);

/**
 * The following block of code may be used to automatically register your
 * Vue components. It will recursively scan this directory for the Vue
 * components and automatically register them with their "basename".
 *
 * Eg. ./components/ExampleComponent.vue -> <example-component></example-component>
 */

// Object.entries(import.meta.glob('./**/*.vue', { eager: true })).forEach(([path, definition]) => {
//     app.component(path.split('/').pop().replace(/\.\w+$/, ''), definition.default);
// });

/**
 * Finally, we will attach the application instance to a HTML element with
 * an "id" attribute of "app". This element is included with the "auth"
 * scaffolding. Otherwise, you will need to add an element yourself.
 */

app.mount('#app');

// Events dropdown hover behavior for desktop
document.addEventListener('DOMContentLoaded', function () {
    const eventsDropdown = document.getElementById('events-dropdown');
    if (!eventsDropdown) return;

    // Check if device supports hover (desktop)
    const hasHover = window.matchMedia('(hover: hover)').matches;

    if (hasHover) {
        const dropdownToggle = eventsDropdown.querySelector('.dropdown-toggle');
        const dropdownMenu = eventsDropdown.querySelector('.dropdown-menu');
        let hoverTimeout;
        const HOVER_DELAY = 150;

        // Open on hover
        eventsDropdown.addEventListener('mouseenter', function () {
            clearTimeout(hoverTimeout);
            const bsDropdown = bootstrap.Dropdown.getInstance(dropdownToggle);
            if (!bsDropdown) {
                new bootstrap.Dropdown(dropdownToggle).show();
            } else {
                bsDropdown.show();
            }
        });

        // Close on mouse leave with delay
        eventsDropdown.addEventListener('mouseleave', function () {
            clearTimeout(hoverTimeout);
            hoverTimeout = setTimeout(function () {
                const bsDropdown = bootstrap.Dropdown.getInstance(dropdownToggle);
                if (bsDropdown) {
                    bsDropdown.hide();
                }
            }, HOVER_DELAY);
        });

        // Keep dropdown open when hovering over the menu itself
        dropdownMenu.addEventListener('mouseenter', function () {
            clearTimeout(hoverTimeout);
        });

        dropdownMenu.addEventListener('mouseleave', function () {
            clearTimeout(hoverTimeout);
            hoverTimeout = setTimeout(function () {
                const bsDropdown = bootstrap.Dropdown.getInstance(dropdownToggle);
                if (bsDropdown) {
                    bsDropdown.hide();
                }
            }, HOVER_DELAY);
        });

        // Keyboard accessibility: open on focus, close on escape
        dropdownToggle.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                const bsDropdown = bootstrap.Dropdown.getInstance(dropdownToggle);
                if (!bsDropdown) {
                    new bootstrap.Dropdown(dropdownToggle).show();
                } else {
                    bsDropdown.toggle();
                }
            }
        });
    }
});
