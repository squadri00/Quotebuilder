import './bootstrap';

import Alpine from 'alpinejs';
import quoteWizard from './quote-wizard';

window.Alpine = Alpine;

Alpine.data('quoteWizard', quoteWizard);

Alpine.start();
