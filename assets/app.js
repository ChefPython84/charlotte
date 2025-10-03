import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';


fetch(`/salle/${salleId}/reservation/new?start=${info.startStr}&end=${info.endStr}`, { 
    headers: { 'X-Requested-With':'XMLHttpRequest' }
})
.then(r => r.text())
.then(html => {
    document.getElementById('reservationModalContent').innerHTML = html;

    const form = document.getElementById('reservation-form');
    form.addEventListener('submit', function(e){
        e.preventDefault();
        const fd = new FormData(form);
        fetch(form.action, {
            method: 'POST',
            headers: { 'X-Requested-With':'XMLHttpRequest' },
            body: fd
        })
        .then(r => r.json())
        .then(json => {
            if(json.success){
                modal.hide();
                calendar.refetchEvents();
                alert('Réservation enregistrée');
            } else {
                alert(json.message || 'Erreur');
            }
        });
    });
});
