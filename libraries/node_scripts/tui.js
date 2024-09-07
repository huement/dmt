#!/usr/bin/env node

/*jshint esversion: 6 */

/**
 * @file Brief description of the file here
 * @author FirstName LastName <optionalEmail@example.com>
 * @copyright FirstName LastName Year
 * @license LicenseHereIfApplicable
 */

// Libraries
// const config = require("../package.json");
// const chalk = require("chalk");
// const cliSelect = require("cli-list-select");
const logo = require("asciiart-logo");
const chalk = require("chalk");
const good = chalk.bold.green;
const info = chalk.bold.cyan;
const bad = chalk.bold.red;
const vapor = chalk.bold.magenta;
const paper = chalk.white;

/**
 * Brief description of the function here.
 * @generator
 * @function functionNameHere
 * @yields {yieldDataType} Brief description of yielded items here.
 */
const textUI = {
  // Header Display
  outputHeader(pc) {
    // Output Logo to Terminal
    console.log(
      logo({
        name: pc.name,
        font: "Cosmike",
        lineChars: 10,
        padding: 2,
        margin: 2,
        borderColor: "black",
        logoColor: "bold-cyan",
        textColor: "bold-magenta",
      })
        .right("ver: " + pc.version)
        .emptyLine()
        .center(pc.description)
        .render()
    );
  },
  outputMiniHeader(pc) {
    console.log(
      logo({
        name: "  " + pc.name + "  ",
        font: "Cosmike",
        lineChars: 20,
        padding: 2,
        margin: 2,
        borderColor: "bold-white",
        logoColor: "bold-cyan",
      })
        .emptyLine()
        .render()
    );
  },
  headerLog(text, hC = 0) {
    // prettier-ignore
    let headerCount = ["⚀","⚁","⚂","⚃","⚄","⚅","⚀⚅","⚁⚅","⚂⚅","⚃⚅","⚄⚅","⚅⚅"];

    let bSpace = " ";
    if (hC > 5) {
      bSpace = " ◼";
    }

    console.log(
      info(
        "  ◼◼◼◼◼◼◼ ◼◼◼◼◼◼◼◼◼ ◼◼◼◼◼◼◼◼◼◼ ◼◼◼ ◼◼◼ " +
          headerCount[hC] +
          " ◼◼◼◼ ◼◼◼◼◼◼◼◼ ◼◼"
      )
    );

    console.log(good("   " + text));
    console.log(
      info(
        "  ◼◼◼ ◼◼◼ ◼◼◼◼◼◼◼◼◼◼◼◼ ◼◼ ◼◼◼◼◼◼ ◼◼◼ ◼◼◼◼◼◼" + bSpace + "◼◼◼◼◼◼◼◼◼ ◼◼◼"
      )
    );
    console.log(" ");
  },
  statusTxt(text) {
    console.log("  " + vapor(text));
    console.log(" ");
  },
  errorTxt(text) {
    console.log("  " + bad(text));
    console.log(" ");
  },
  makeADate() {
    // prettier-ignore
    const monthNames = ["JAN","FEB","MAR","APR","MAY","JUNE","JULY","AUG","SEPT","OCT","NOV","DEC"];

    let date_ob = new Date();
    let date = ("0" + date_ob.getDate()).slice(-2);
    let month = monthNames[date_ob.getMonth()];
    let year = date_ob.getFullYear();
    let dateString = month + " " + date + " " + year;

    return dateString;
  },
};

//outputHeader(config);

// export the courses so other modules can use them
exports.textUI = textUI;
