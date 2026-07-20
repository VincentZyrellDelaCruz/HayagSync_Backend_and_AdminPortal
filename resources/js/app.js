import './bootstrap';
import './elements/turbo-echo-stream-tag';
import './libs';
import Alpine from 'alpinejs';
import * as Turbo from '@hotwired/turbo';

window.Alpine = Alpine;
Alpine.start();
Turbo.start();
