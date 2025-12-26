const path = require("path");
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const RemoveEmptyScriptsPlugin = require( 'webpack-remove-empty-scripts' );

module.exports = {
    entry: {
        "wpsslc-admin-setting": "./assets/js/wpsslc-admin-setting.js",
        "wpsslc-general": "./assets/js/wpsslc-general.js",
        "admin-feedback": "./feedback/js/admin-feedback.js",
        
        "wpsslc-admin-plugin-setting-style": "./assets/css/wpsslc-admin-plugin-setting-style.css",
        "wpsslc-admin-review": "./assets/css/wpsslc-admin-review.css",
        "wpsslc-admin-setting": "./assets/css/wpsslc-admin-setting.css",
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
