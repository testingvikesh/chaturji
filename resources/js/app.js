import './bootstrap';

import Alpine from 'alpinejs';
import { initAnimations } from './animations';
import { registerObjectiveAttempt } from './objective-attempt';
import { initPwa, registerPwaInstall } from './pwa';

window.Alpine = Alpine;

initPwa();
registerPwaInstall(Alpine);
registerObjectiveAttempt(Alpine);

Alpine.start();

document.addEventListener('DOMContentLoaded', initAnimations);
