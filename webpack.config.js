/**
 * Webpack build for Souvera Archive.
 */
const path = require('path')
const { VueLoaderPlugin } = require('vue-loader')

module.exports = (env, argv) => {
	const isProduction = argv.mode === 'production'

	return {
		entry: {
			main: path.join(__dirname, 'src', 'main.js'),
		},
		output: {
			path: path.resolve(__dirname, 'js'),
			filename: 'souvera_mailarchiv-[name].js',
			chunkFilename: 'souvera_mailarchiv-[name].js?v=[contenthash]',
			publicPath: 'auto',
			clean: true,
		},
		devtool: isProduction ? false : 'source-map',
		module: {
			rules: [
				{
					test: /\.vue$/,
					loader: 'vue-loader',
				},
				{
					test: /\.m?js$/,
					resolve: { fullySpecified: false },
				},
				{
					test: /\.js$/,
					loader: 'babel-loader',
					exclude: /node_modules/,
				},
				{
					test: /\.css$/,
					use: ['style-loader', 'css-loader'],
				},
				{
					test: /\.scss$/,
					use: ['style-loader', 'css-loader', 'sass-loader'],
				},
				{
					test: /\.(png|jpe?g|gif|svg|woff2?|eot|ttf)$/,
					type: 'asset/inline',
				},
			],
		},
		plugins: [
			new VueLoaderPlugin(),
		],
		resolve: {
			extensions: ['.js', '.mjs', '.vue'],
			alias: {
				'@': path.resolve(__dirname, 'src'),
			},
		},
	}
}
