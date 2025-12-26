const path = require( 'path' );
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const RemoveEmptyScriptsPlugin = require( 'webpack-remove-empty-scripts' );

module.exports = {
	entry: {
		'wpssle-admin-script': './assets/js/wpssle-admin-script.js',
		'wpssle-custom-elementor': './assets/js/wpssle-custom-elementor.js',
		'wpssle-general': './assets/js/wpssle-general.js',
		'admin-feedback': './feedback/js/admin-feedback.js',

		'wpssle-admin-plugin-setting-style':
			'./assets/css/wpssle-admin-plugin-setting-style.css',
		'wpssle-admin-review': './assets/css/wpssle-admin-review.css',
		'wpssle-admin-style': './assets/css/wpssle-admin-style.css',
		'admin-feedback-style': './feedback/css/admin-feedback-style.css',
	},

	output: {
		path: path.resolve( __dirname, './assets' ),
		filename: 'js/build/[name].js',
		clean: false,
	},

	module: {
		rules: [
			{
				test: /\.js$/,
				exclude: /node_modules/,
				use: 'babel-loader', // optional, @wordpress/scripts provides env config automatically
			},
			{
				test: /\.(scss|css)$/,
				use: [
					MiniCssExtractPlugin.loader,
					{
						loader: 'css-loader',
						options: {
							url: false, // important for WP — avoids image path rewriting
						},
					},
					{
						loader: 'postcss-loader',
						options: {
							postcssOptions: {
								plugins: [ require( 'autoprefixer' ) ],
							},
						},
					},
					'sass-loader',
				],
			},
		],
	},

	plugins: [
		new RemoveEmptyScriptsPlugin(),
		new MiniCssExtractPlugin( {
			filename: 'css/build/[name].css',
		} ),
	],
};
