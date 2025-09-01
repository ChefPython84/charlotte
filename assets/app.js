import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css'


import './bootstrap.js';
import './styles/app.css';
import './styles/fullcalendar.css';

import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import bootstrap5Plugin from '@fullcalendar/bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const calendarEl = document.getElementById('calendar');
    if (!calendarEl) return;

    const calendar = new Calendar(calendarEl, {
        plugins: [dayGridPlugin, interactionPlugin, bootstrap5Plugin],
        initialView: 'dayGridMonth',
        themeSystem: 'bootstrap5',
        selectable: true,
        editable: false,
        events: [
            { title: 'Réservé', start: '2025-09-05' },
            { title: 'Anniversaire', start: '2025-09-10', end: '2025-09-12' }
        ],
    });

    calendar.render();
});



console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉')
