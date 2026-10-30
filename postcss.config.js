const path = require("path");
const fs = require("fs");

const isProduction = process.env.NODE_ENV === "production";

module.exports = {
    plugins: [
        require("postcss-import")({
            resolve: (id, basedir) => {
                if (id.startsWith("npm/")) {
                    const packagePath = path.resolve(
                        "node_modules",
                        id.slice(4),
                    );
                    if (
                        fs.existsSync(packagePath) &&
                        fs.lstatSync(packagePath).isDirectory()
                    ) {
                        return fs
                            .readdirSync(packagePath)
                            .filter((file) => file.endsWith(".css"))
                            .map((file) => path.join(packagePath, file));
                    }
                    return packagePath;
                }

                const dirPath = path.resolve(basedir, id);
                if (
                    fs.existsSync(dirPath) &&
                    fs.lstatSync(dirPath).isDirectory()
                ) {
                    return fs
                        .readdirSync(dirPath)
                        .filter((file) => file.endsWith(".css"))
                        .sort()
                        .map((file) => path.join(dirPath, file));
                }

                return id;
            },
        }),
        require("postcss-nested").default,
        require("postcss-preset-env")({ stage: 1 }),
        ...(isProduction ? [require("cssnano")({ preset: "default" })] : []),
    ],
};
