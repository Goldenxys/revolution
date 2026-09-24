import './bootstrap';

import Alpine from 'alpinejs';
import commandeForm from './commande-form';
import commandeCatalogue from './commande-catalogue';
import commandeDemande from './commande-demande';
import initPageLoader from './page-loader';

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    Alpine.data('commandeForm', commandeForm);
    Alpine.data('commandeCatalogue', commandeCatalogue);
    Alpine.data('commandeDemande', commandeDemande);
});

Alpine.start();
initPageLoader();
