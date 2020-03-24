const mix = require('laravel-mix');

function resolve(dir) {
    return path.join(
        __dirname,
        '/resources/js',
        dir
    );
}

Mix.listen('configReady', webpackConfig => {
    // Add "svg" to image loader test
    const imageLoaderConfig = webpackConfig.module.rules.find(
        rule =>
            String(rule.test) ===
            String(/(\.(png|jpe?g|gif|webp)$|^((?!font).)*\.svg$)/)
    );
    imageLoaderConfig.exclude = resolve('icons');
});

mix.js('resources/js/app.js', 'public/js')
    .sass('resources/sass/app.scss', 'public/css')
    // 设置路径别名
    .webpackConfig({
        resolve: {
            alias: {
                '@': path.join(__dirname, '/resources')
            }
        },
        module: {
            rules: [
                {
                    test: /\.svg$/,
                    loader: 'svg-sprite-loader',
                    include: [resolve('icons')],
                    options: {
                        symbolId: 'icon-[name]',
                    },
                },
            ],
        },
    })
    .disableNotifications()
    .browserSync('www.laravel.test')
