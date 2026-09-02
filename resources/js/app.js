import './bootstrap';

import Alpine from 'alpinejs';
import commandeForm from './commande-form';
import commandeCatalogue from './commande-catalogue';
import carteFidelite from './carte-fidelite';
import initPageLoader from './page-loader';

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    Alpine.data('commandeForm', commandeForm);
    Alpine.data('commandeCatalogue', commandeCatalogue);
    Alpine.data('carteFidelite', carteFidelite);
});

Alpine.start();
initPageLoader();
