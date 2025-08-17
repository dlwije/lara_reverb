'use strict';

import { route } from 'ziggy-js';
import { router } from '@inertiajs/vue3';

function shortcuts(e) {
  if (e.ctrlKey && e.shiftKey) {
    switch (e.code) {
      case 'KeyS':
        router.visit(route('sales.index'));
        break;
      case 'KeyC':
        router.visit(route('customers.index'));
        break;
    }
  } else if (e.ctrlKey && e.altKey) {
    switch (e.code) {
      case 'KeyX':
        router.visit(route('pos'));
        break;
      case 'KeyS':
        router.visit(route('sales.create'));
        break;
      case 'KeyC':
        router.visit(route('customers.index', { create: true }));
        break;
    }
  }
}

export default shortcuts;
