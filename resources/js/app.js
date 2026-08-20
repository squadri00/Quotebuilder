import './bootstrap';

import Alpine from 'alpinejs';
import AOS from 'aos';
import 'aos/dist/aos.css';
import quoteWizard from './quote-wizard';

window.Alpine = Alpine;

Alpine.data('quoteWizard', quoteWizard);

Alpine.start();

AOS.init({ once: true, duration: 600 });
