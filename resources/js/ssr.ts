import { createInertiaApp } from '@inertiajs/vue3';
import createServer from '@inertiajs/vue3/server';
import { renderToString } from '@vue/server-renderer';
import { createSSRApp, h } from 'vue';
import type { DefineComponent } from 'vue';

createServer((page) =>
    createInertiaApp({
        page,
        render: renderToString,
        resolve: (name) => {
            const pages = import.meta.glob<DefineComponent>('./pages/**/*.vue', { eager: true });
            const pageComponent = pages[`./pages/${name}.vue`];

            if (!pageComponent) {
                throw new Error(`Page not found: ./pages/${name}.vue`);
            }
            
            return pageComponent;
        },
        setup({ App, props, plugin }) {
            return createSSRApp({
                render: () => h(App, props),
            }).use(plugin);
        },
    })
);
