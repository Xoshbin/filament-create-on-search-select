const esbuild = require('esbuild')
const path = require('path')

const isDev = process.argv.includes('--dev')

async function build() {
    const options = {
        entryPoints: [path.resolve(__dirname, '../resources/js/index.js')],
        outfile: path.resolve(__dirname, '../resources/dist/filament-create-on-search-select.js'),
        bundle: true,
        platform: 'browser',
        mainFields: ['module', 'main'],
        minify: !isDev,
        sourcemap: isDev,
    }

    if (isDev) {
        options.watch = true
    }

    try {
        await esbuild.build(options)
    } catch (error) {
        console.error(error)
        process.exit(1)
    }
}

build()
