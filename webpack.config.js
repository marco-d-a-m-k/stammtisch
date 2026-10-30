var glob = require("glob");
const path = require("path");

const isProduction = process.env.NODE_ENV === "production";

module.exports = {
    entry: {
        js: glob.sync("./src/js/**/*.js"),
    },
    output: {
        filename: "script.js",
        path: path.resolve(__dirname, "build"),
        // keep CSS output alone since postcss writes into the same "build" dir
        clean: { keep: /\.css$/ },
    },
    mode: isProduction ? "production" : "development",
    devtool: isProduction ? false : "source-map",
    module: {
        rules: [
            {
                test: /\.(js)$/,
                exclude: /node_modules/,
                use: ["babel-loader"],
            },
        ],
    },
    resolve: {
        preferRelative: true,
        extensions: [".js"],
    },
};
