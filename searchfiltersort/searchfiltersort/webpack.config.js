const path = require("path");
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const RemoveEmptyScriptsPlugin = require( 'webpack-remove-empty-scripts' );

module.exports = {
    externals: {
        jquery: 'jQuery',
        nouislider: 'noUiSlider'
    },
    entry: {
        "admin": "./assets/js/admin.js",
        "frontend": "./assets/js/frontend.js",

        "admin-style": "./assets/css/admin-style.css",
        "frontend-style": "./assets/css/frontend-style.css"
    },

    output: {
        path: path.resolve(__dirname, "./assets"),
        filename: "js/build/[name].js",
        clean: false,
    },

    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: /node_modules/,
                use: "babel-loader" // optional, @wordpress/scripts provides env config automatically
            },
            {
                test: /\.(scss|css)$/,
                use: [
                    MiniCssExtractPlugin.loader,
                    {
                        loader: "css-loader",
                        options: {
                            url: false // important for WP — avoids image path rewriting
                        }
                    },
                    {
                        loader: "postcss-loader",
                        options: {
                            postcssOptions: {
                                plugins: [require("autoprefixer")]
                            }
                        }
                    },
                    "sass-loader"
                ]
            }
        ]
    },

    plugins: [
        new RemoveEmptyScriptsPlugin(),
        new MiniCssExtractPlugin({
            filename: "css/build/[name].css"
        })
    ]
};
