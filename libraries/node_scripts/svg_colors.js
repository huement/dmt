// Give it an SVG filename

// You'll get back an object with two keys: `fills` and `strokes`

// `fills` is an array of chroma-js objects
//console.log(colors.fills.map(color => color.hex()));

// `strokes` is also an array of chroma-js objects
//console.log(colors.strokes.map(color => color.hex()));

// Crazy stuff...
// console.log(colors.fills[0].alpha(0.5).css());

// Pass the `flat` option to get back a single array including
// de-duped fills and strokes together
//const colors = getColors('<svg...>', { flat: true })

"use strict";

const getColors = require("get-svg-colors");
const FS = require("fs");
const PATH = require("path");
const SVGO = require("../svg/svgo/bin/svgo");
const glob = require("glob");
const { textUI } = require("./tui");
const cg = require("./package.json");

function baseName(str) {
    var base = new String(str).substring(str.lastIndexOf("/") + 1);
    if (base.lastIndexOf(".") != -1)
        base = base.substring(0, base.lastIndexOf("."));
    return base;
}

function svgClean(filename) {
    var FS = require("fs");
    var PATH = require("path");
    var SVGO = require("./libraries/svgo/lib/svgo");
    var filepath = PATH.resolve(__dirname, filename);

    let svgo = new SVGO({
        plugins: [
            {
                cleanupAttrs: true,
            },
            {
                removeDoctype: true,
            },
            {
                removeXMLProcInst: true,
            },
            {
                removeComments: true,
            },
            {
                removeMetadata: true,
            },
            {
                removeTitle: true,
            },
            {
                removeDesc: true,
            },
            {
                removeUselessDefs: true,
            },
            {
                removeEditorsNSData: true,
            },
            {
                removeEmptyAttrs: true,
            },
            {
                removeHiddenElems: true,
            },
            {
                removeEmptyText: true,
            },
            {
                removeEmptyContainers: true,
            },
            {
                removeViewBox: false,
            },
            {
                cleanupEnableBackground: true,
            },
            {
                convertStyleToAttrs: false,
            },
            {
                convertColors: true,
            },
            {
                convertPathData: false,
            },
            {
                convertTransform: false,
            },
            {
                removeUnknownsAndDefaults: true,
            },
            {
                removeNonInheritableGroupAttrs: false,
            },
            {
                removeUselessStrokeAndFill: false,
            },
            {
                removeUnusedNS: true,
            },
            {
                cleanupIDs: true,
            },
            {
                cleanupNumericValues: true,
            },
            {
                moveElemsAttrsToGroup: false,
            },
            {
                moveGroupAttrsToElems: false,
            },
            {
                collapseGroups: false,
            },
            {
                removeRasterImages: true,
            },
            {
                mergePaths: false,
            },
            {
                convertShapeToPath: false,
            },
            {
                sortAttrs: true,
            },
            {
                removeDimensions: false,
            },
        ],
    });

    FS.readFile(filepath, "utf8", function (err, data) {
        if (err) {
            throw err;
        }

        svgo.optimize(data, { path: filepath }).then(function (result) {
            //console.log(result);
            let svgobase = baseName(filepath);
            let svgoname = "output/optimized/" + svgobase + ".svg";

            FS.writeFile(svgoname, result.data, function (err) {
                if (err) return console.log(err);
                console.log("Cleaned: " + filepath);
            });
        });
    });
}

if (process.argv.length <= 2) {
    textUI.errorTxt("Usage: " + __filename + " path/to/directory");
    process.exit(-1);
}

const basePath = process.argv[2]; //Getting the path (it works)

textUI.outputHeader(cg);

var options = { nonull: true };

glob(basePath + "/*.svg", options, function (er, files) {
    let Filenames = [];

    // files is an array of filenames.
    // If the `nonull` option is set, and nothing
    // was found, then files is ["**/*.js"]
    // er is an error object or null.
    files.forEach(function (element) {
        let bn = baseName(element);

        //let cn = "output/resized/" + bn + ".svg";
        //svgClean(cn);

        let colors = getColors(element);
        let icon = {
            name: bn,
            fills: colors.fills.map((color) => color.hex()),
            strokes: colors.strokes.map((color) => color.hex()),
        };

        let dirname = PATH.dirname(element);
        let data = JSON.stringify(icon);
        FS.writeFileSync(dirname + "/" + bn + ".json", data);

        console.log("Wrote: " + dirname + "/" + bn + ".json");
        Filenames.push(bn + ".svg");
    });

    console.log(" ");
    console.log("Processed: " + Filenames.length + " files");
    console.log(" ");
});

// let icon = {
//     fills: colors.fills.map(color => color.hex()),
//     strokes: colors.strokes.map(color => color.hex())
// };

// let data = JSON.stringify(icon);
// fs.writeFileSync(__dirname + '/cleaned/bank-513.json', data);
