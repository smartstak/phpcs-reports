const path = require("path");
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const RemoveEmptyScriptsPlugin = require( 'webpack-remove-empty-scripts' );

module.exports = {
    entry: {
        "wpsslgf-admin-script": "./assets/js/wpsslgf-admin-script.js",
        "wpsslgf-general": "./assets/js/wpsslgf-general.js",
        "wpsslgf-plugin-settings": "./assets/js/wpsslgf-plugin-settings.js",
        "admin-feedback": "./feedback/js/admin-feedback.js",
        
        "wpsslgf-admin-plugin-setting-style": "./assets/css/wpsslgf-admin-plugin-setting-style.css",
        "wpsslgf-admin-review": "./assets/css/wpsslgf-admin-review.css",
        "wpsslgf-admin-style": "./assets/css/wpsslgf-admin-style.css",
        "wpsslgf-feed-pro": "./assets/css/wpsslgf-feed-pro.css",
        "admin-feedback-style": "./feedback/css/admin-feedback-style.css"
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
