import './bootstrap';

import Alpine from 'alpinejs';
import React from 'react';
import { createRoot } from 'react-dom/client';
import PublicHome from './pages/PublicHome';
import Dashboard from './pages/Dashboard';

window.Alpine = Alpine;

Alpine.start();

const mountReact = (id, Component) => {
    const el = document.getElementById(id);
    if (!el) return;

    let props = {};
    if (el.dataset.props) {
        try {
            props = JSON.parse(el.dataset.props);
        } catch (error) {
            console.error(`Invalid JSON props for ${id}`, error);
        }
    }

    createRoot(el).render(<Component {...props} />);
};

mountReact('home-root', PublicHome);
mountReact('dashboard-root', Dashboard);
