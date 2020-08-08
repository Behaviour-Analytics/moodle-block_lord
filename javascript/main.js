/**
 * Function to wait for AMD modules to load.
 *
 * @param {object} Y - Some internal Moodle thing that gets passed by default.
 * @param {object} incoming - The incoming data from the server.
 */
function waitForModules(Y, incoming) { // eslint-disable-line

    if (window.dataDrivenDocs && window.lord) {
        window.lord(incoming);
    } else {
        setTimeout(waitForModules.bind(this, Y, incoming), 200);
    }
}
