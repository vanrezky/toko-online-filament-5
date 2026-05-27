import tailwindv3 from 'tailwindcss-v3';
import tailwindv4 from '@tailwindcss/postcss';
import autoprefixer from 'autoprefixer';

const PROCESSED = Symbol('PROCESSED');

export default {
    plugins: [
        {
            postcssPlugin: 'tailwind-router',
            async Once(root, { result, postcss }) {
                if (root[PROCESSED]) return;

                const file = result.opts.from || '';
                const isFilament = file.includes('/filament/') || file.includes('node_modules/tailwindcss') || file.includes('node_modules/@tailwindcss');

                // Clone the root to avoid modifying the original during sub-process
                const cleanRoot = root.clone();
                cleanRoot[PROCESSED] = true;

                const plugins = [];
                if (isFilament) {
                    plugins.push(tailwindv4());
                } else {
                    plugins.push(tailwindv3(), autoprefixer());
                }

                const processor = postcss(plugins);
                const newResult = await processor.process(cleanRoot, {
                    ...result.opts,
                });

                root.removeAll();
                root.append(newResult.root.nodes);
            }
        }
    ]
};
