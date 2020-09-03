// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file defines the client side logic for the LORD plugin, which includes
 * rendering the graph and related interface, calculating the final similarity
 * weights between nodes, showing the node content and similarity matrices, and
 * sending the node and link data back to the server for storage.
 *
 * @package block_lord
 * @author Ted Krahn
 * @copyright 2020 Athabasca University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

(function(factory) {
    if (typeof define === "function" && define.amd) {
        // AMD. Register as an anonymous module.
        define([], factory);
    } else {
        // Browser globals.
        window.lord = factory();
    }
})(function() {

    var lord = function(incoming) {

        var ddd, // D3.
            munkres, // Hungarian algorithm.
            colours, // The colours array.
            modColours, // Learning module colours.
            width, // Of graph area.
            height, // Of graph area.
            nodeRadius, // Radius of graph nodes.
            modules, // The learning modules.
            graphData, // Node and link data for graph.
            graph, // The network graph.
            graphNodes, // The graph nodes.
            graphLinks, // The graph links.
            weights, // The link weights.
            coordsScript, // URL of save graph script.
            sessionKey, // Session key.
            courseId, // Course id.
            names, // Array of module names.
            paragraphs, // Array of module paragraph content.
            sentences, // Array of content split into cleaned sentences.
            comparisonWeights, // Array of weights for different comparison types.
            node1, // Node id for left side content.
            node2, // Node id for right side content.
            matrices, // The similarity matrices.
            introCost, // All intros cost matrix.
            sentenceCost, // Array of sentence cost matrices.
            paragraphCost, // All paragraphs cost matrix.
            simulation, // The physics simulation.
            minNodeDist, // Minimum distance between nodes.
            maxNodeDist, // Maximum distance between nodes.
            nodeDistScale, // Node distance scaling factor.
            presetNodes, // Pre-defined node coordinates.
            coordsScale, // Scale value for graph.
            usingCustomGraph, // Flag to determine which graph is rendered.
            simulationTimeout, // Time in milliseconds physics simulation will run.
            distanceScales; // Array of graphing interface slider values.

        /**
         * Initialize the program. This function sets various default values and
         * initializes various variables, then calls the necessary functions to run the
         * program.
         *
         * @param {array} incoming Data from server
         */
        function init(incoming) {

            modules = incoming.modules;
            weights = incoming.weights;
            coordsScript = incoming.coordsscript;
            courseId = incoming.courseid;
            sessionKey = incoming.sesskey;
            names = incoming.names;
            paragraphs = incoming.paragraphs;
            sentences = incoming.sentences;
            matrices = incoming.matrices;
            comparisonWeights = incoming.comparisonweights;
            presetNodes = incoming.presetnodes;

            // Get external packages.
            ddd = window.dataDrivenDocs;
            munkres = window.munkres;

            // Get base values for various variables.
            colours = getColours();

            modColours = {
                'originalLinks': 'lightgrey', // Removed from colours[].
                'grouping':      'black', // Removed from colours[].
                'assign':        'blue',
                'quiz':          'red',
                'forum':         'orange',
                'resource':      'green',
                'lti':           'yellow',
                'url':           'purple',
                'book':          'magenta',
                'page':          'cyan',
                'lesson':        'brown',
            };

            width = window.innerWidth - 150;
            height = window.innerHeight - 90;

            nodeRadius = Math.min(width, height) < 500 ? 6 : 8;

            // Initialize some defaults.
            minNodeDist = 25;
            maxNodeDist = 200;
            nodeDistScale = 1000;
            simulationTimeout = 7000;

            assignModuleColours();

            // Use user generated graph.
            if (Object.keys(presetNodes.usergraph).length > 0) {

                coordsScale = presetNodes.userscale;
                minNodeDist = presetNodes.usermindist;
                maxNodeDist = presetNodes.usermaxdist;
                nodeDistScale = presetNodes.userdistscale;

                // Make the graph.
                getData(presetNodes.usergraph);
                initGraph(tick1, true);
                addGraphInterface();
                usingCustomGraph = true;
                showGraphMessage('usergraph');

            } else if (Object.keys(presetNodes.systemgraph).length > 0) {
                // Use system generated graph

                coordsScale = presetNodes.systemscale;
                minNodeDist = presetNodes.systemmindist;
                maxNodeDist = presetNodes.systemmaxdist;
                nodeDistScale = presetNodes.systemdistscale;

                // Make the graph.
                getData(presetNodes.systemgraph);
                initGraph(tick1, true);
                addGraphInterface();
                usingCustomGraph = false;
                showGraphMessage('systemgraph');

            } else {
                // No graphs generated yet, generate one.
                getData({});
                initGraph(tick, false);

                setTimeout(function() {
                    sendCoordsToServer(false);
                    addGraphInterface();
                    usingCustomGraph = false;
                    showGraphMessage('graphsaved');
                    graphNodes.call(ddd.drag()
                                    .on('start', dragstarted)
                                    .on('drag', dragged)
                                    .on('end', dragended));
                }, simulationTimeout);
            }

            // Store last used slider values.
            distanceScales = {
                min: minNodeDist,
                max: maxNodeDist,
                scale: nodeDistScale
            };
        }

        // Change english names to hex values.
        /**
         * Returns an array of select (darker) html colour names taken from
         * https://www.w3schools.com/colors/colors_names.asp.
         *
         * @return {array}
         */
        function getColours() {

            var c = ['aqua', 'blue', 'blueviolet', 'brown',
                     'cadetblue', 'chartreuse', 'chocolate', 'coral', 'cornflowerblue',
                     'crimson', 'cyan', 'darkblue', 'darkcyan', 'darkgoldenrod',
                     'darkgrey', 'darkgreen', 'darkmagenta', 'darkolivegreen',
                     'darkorange', 'darkorchid', 'darkred', 'darksalmon',
                     'darkseagreen', 'darkslateblue', 'darkslategrey', 'darkturquoise',
                     'darkviolet', 'deeppink', 'deepskyblue', 'dimgrey', 'dodgerblue',
                     'firebrick', 'forestgreen', 'fuchsia', 'gold', 'goldenrod', 'grey',
                     'green', 'greenyellow', 'hotpink', 'indianred', 'indigo', 'khaki',
                     'lawngreen', 'lightblue', 'lightcoral', 'lightgreen',
                     'lightpink', 'lightsalmon', 'lightseagreen', 'lightskyblue',
                     'lightslategrey', 'lightsteelblue', 'lime', 'limegreen', 'magenta',
                     'maroon', 'mediumaquamarine', 'mediumblue', 'mediumorchid',
                     'mediumpurple', 'mediumseagreen', 'mediumslateblue',
                     'mediumspringgreen', 'mediumturquoise', 'mediumvioletred',
                     'midnightblue', 'navy', 'olive', 'olivedrab', 'orange', 'orangered',
                     'orchid', 'palegreen', 'paleturquoise', 'palevioletred', 'peru',
                     'plum', 'powderblue', 'purple', 'rebeccapurple', 'red', 'rosybrown',
                     'royalblue', 'saddlebrown', 'salmon', 'sandybrown', 'seagreen',
                     'sienna', 'silver', 'skyblue', 'slateblue', 'slategrey',
                     'springgreen', 'steelblue', 'tan', 'teal', 'thistle', 'tomato',
                     'turquoise', 'violet', 'yellow', 'yellowgreen'];
            return c;
        }

        /**
         * Ensure all module types have assigned colour. This will account for unknown
         * module types.
         */
        function assignModuleColours() {

            modules.forEach(function(m) {
                while (!modColours[m.entype]) {

                    // Pick random colour, but make sure it is not a duplicate.
                    var c = colours[Math.floor(Math.random() * colours.length)];
                    var isOKColour = true;

                    for (var key in modColours) {
                        if (modColours[key] == c) {
                            isOKColour = false;
                        }
                    }
                    modColours[m.entype] = isOKColour ? c : undefined;
                }
            });
        }

        /**
         * Makes the node and link data from the information passed by the server.
         *
         * @param {object} vertices - Pre-defined node coordinates.
         */
        function getData(vertices) {

            var nodes = [],
                links = [],
                ob = {},
                node = {};

            // Make nodes from modules.
            modules.forEach(function(m) {

                // Regular module node.
                node = {
                    id:      m.id,
                    name:    m.name,
                    type:    m.type,
                    entype:  m.entype,
                    colour:  modColours[m.entype],
                    visible: true,
                };
                if (vertices[m.id]) {
                    node.xcoord = vertices[m.id].xcoord;
                    node.ycoord = vertices[m.id].ycoord;
                }
                nodes[nodes.length] = node;

                // Section grouping node.
                if (!ob[m.sect]) {
                    node = {
                        id:      'g' + m.sect,
                        name:    M.util.get_string('section', 'block_lord') + ' ' + m.sect,
                        group:   m.sect,
                        type:    'grouping',
                        colour:  modColours.grouping,
                        visible: false,
                    };
                    if (vertices['g' + m.sect]) {
                        node.xcoord = vertices['g' + m.sect].xcoord;
                        node.ycoord = vertices['g' + m.sect].ycoord;
                    }


                    ob[m.sect] = node;
                    nodes[nodes.length] = node;
                }

                // Link module node to section.
                links[links.length] = {
                    source: 'g' + m.sect,
                    target: m.id,
                    weight: 0,
                    colour: modColours.originalLinks
                };
            });

            // Root grouping node.
            node = {
                id:      'root',
                name:    'root',
                group:   -1,
                type:    'grouping',
                colour:  modColours.grouping,
                visible: false,
            };
            if (vertices.root) {
                node.xcoord = vertices.root.xcoord;
                node.ycoord = vertices.root.ycoord;
            }
            nodes[nodes.length] = node;

            // Link other group nodes to root course node.
            for (var o in ob) {
                links[links.length] = {
                    source: 'root',
                    target: ob[o].id,
                    weight: 0,
                    colour: modColours.originalLinks
                };
            }

            // Make the weighted links, one for every pair of modules.
            var key,
                weight;
            ob = {};
            modules.forEach(function(m1) {
                modules.forEach(function(m2) {

                    key = parseInt(m1.id) < parseInt(m2.id) ? m1.id + '_' + m2.id : m2.id + '_' + m1.id;

                    if (m1.id != m2.id && !ob[key]) {
                        ob[key] = 1;
                        weight = 0;

                        if (!weights[key] || Array.isArray(weights[key])) {
                            weight = -0.01;
                        } else {
                            weight = getWeight(key);
                        }

                        links[links.length] = {
                            source: m1.id,
                            target: m2.id,
                            weight: weight,
                            colour: modColours.originalLinks
                        };
                    }
                });
            });

            graphData = {nodes: nodes, links: links};
        }

        /**
         * Function to calculate the final comparison value for.
         *
         * @param {string} key - The key into the weights array.
         * @return {number}
         */
        function getWeight(key) {

            var nameWeight = 0;
            var maxI = 0;
            var maxP = 0;
            var p1 = 0;
            var p2 = 0;
            var split = [];
            var intros = {};
            var paras = {};

            for (var w in weights[key]) {

                if (w == 'name') { // Get the weight for names comparison.
                    nameWeight += parseFloat(weights[key][w]) * parseFloat(comparisonWeights.names);

                } else if (w.startsWith('intro')) { // Build intros array.
                    intros[w] = parseFloat(weights[key][w]);
                    split = w.slice(5).split('x');

                    if (parseInt(split[0]) > maxI) {
                        maxI = parseInt(split[0]);
                    }
                    if (parseInt(split[1]) > maxI) {
                        maxI = parseInt(split[1]);
                    }

                } else { // Build paragraphs array.
                    split = w.split('P');

                    p1 = split[1].split('S');
                    if (maxP < p1[0]) {
                        maxP = p1[0];
                    }
                    p2 = split[2].split('S');
                    if (maxP < p2[0]) {
                        maxP = p2[0];
                    }

                    if (!paras[p1[0] + '_' + p2[0]]) {
                        paras[p1[0] + '_' + p2[0]] = {};
                    }
                    paras[p1[0] + '_' + p2[0]][p1[1] + '_' + p2[1]] = parseFloat(weights[key][w]);
                }
            }

            // Get weights for intros and paragraphs.
            var introWeight = getIntroWeight(maxI, intros);
            var paragraphWeight = getParagraphWeight(maxP, paras);

            // Determine final weight for this comparison.
            if (paragraphWeight != 0.0) {
                return (nameWeight + introWeight + paragraphWeight) / 3.0;

            } else {
                return (nameWeight + introWeight) / 2.0;
            }
        }

        /**
         * Function to calculate the weight for the module introductions.
         *
         * @param {number} maxI - The size of the intro cost matrix.
         * @param {array} intros - An array of introduction comparison values.
         * @return {number}
         */
        function getIntroWeight(maxI, intros) {

            // Build the cost array with default values.
            var cost = [];
            var i = 0;
            var j = 0;
            for (i = 0; i <= maxI; i++) {
                cost[i] = [];
                for (j = 0; j <= maxI; j++) {
                    cost[i][j] = -1000;
                }
            }

            // Replace default values with actual values, where applicable.
            var split = [];
            i = 0;
            for (i in intros) {
                split = i.slice(5).split('x');
                cost[parseInt(split[0])][parseInt(split[1])] = intros[i];
            }

            // Convert cost matrix to profit matrix and get optimal assignment.
            var profit = munkres.make_cost_matrix(cost);
            var optimal = munkres(profit);

            // Calculate the mean optimal assignment value, ignoring default values.
            var introWeight = 0;
            var n = 0;
            var o = 0;
            for (i = 0; i < optimal.length; i++) {
                o = cost[optimal[i][0]][optimal[i][1]];
                if (o >= -1.0) {
                    introWeight += o;
                    n++;
                }
            }

            // Weight intros and return final value.
            introWeight = n > 0 ? introWeight / n : 0.0;
            introCost = {matrix: cost, optimal: optimal, weight: introWeight};
            introWeight *= parseFloat(comparisonWeights.intros);

            return introWeight;
        }

        /**
         * Function to calculate the weight for the module paragraphs.
         *
         * @param {number} maxP - The size of the paragraph cost matrix.
         * @param {array} paras - An array of sentence comparison values.
         * @return {number}
         */
        function getParagraphWeight(maxP, paras) {

            // Build the paragraph cost array with default values.
            var costP = [];
            var i = 0;
            var j = 0;
            for (i = 0; i <= maxP; i++) {
                costP[i] = [];
                for (j = 0; j <= maxP; j++) {
                    costP[i][j] = -1000;
                }
            }

            var splitP = [];
            var splitS = [];
            var costS = [];
            var keys = [];
            var maxS = 0;
            var a = 0;
            var b = 0;
            var profit = [];
            var optimal = [];
            var sentenceWeight = 0;
            var n = 0;
            var k = 0;
            var o = 0;
            sentenceCost = {};

            // Process the paragraphs.
            for (var p in paras) {
                splitP = p.split('_');
                keys = Object.keys(paras[p]);

                if (keys.length == 0) { // No sentences to process.
                    continue;

                } else {
                    maxS = 0;
                    a = 0;
                    b = 0;

                    // Determine the size of the sentence matrix.
                    for (k in keys) {
                        splitS = keys[k].split('_');

                        a = parseInt(splitS[0]);
                        if (maxS < a) {
                            maxS = a;
                        }
                        b = parseInt(splitS[1]);
                        if (maxS < b) {
                            maxS = b;
                        }
                    }

                    // Build the sentence matrix with default values.
                    costS = [];
                    for (i = 0; i <= maxS; i++) {
                        costS[i] = [];
                        for (j = 0; j <= maxS; j++) {
                            costS[i][j] = -1000;
                        }
                    }

                    // Fill in the other sentence matrix values.
                    for (k in keys) {
                        splitS = keys[k].split('_');
                        a = parseInt(splitS[0]);
                        b = parseInt(splitS[1]);
                        costS[a][b] = paras[p][keys[k]];
                    }

                    // Convert cost to profit matrix and get optimal assignment.
                    profit = munkres.make_cost_matrix(costS);
                    optimal = munkres(profit);

                    // Calculate mean sentence weight from optimal assignment.
                    n = 0;
                    sentenceWeight = 0;
                    for (i = 0; i < optimal.length; i++) {
                        o = costS[optimal[i][0]][optimal[i][1]];
                        if (o >= -1.0) {
                            sentenceWeight += o;
                            n++;
                        }
                    }
                    sentenceWeight = n > 0 ? sentenceWeight / n : 0.0;
                    sentenceCost[p] = {matrix: costS, optimal: optimal, weight: sentenceWeight};

                    // Add sentence weight to paragraph matrix.
                    a = parseInt(splitP[0]);
                    b = parseInt(splitP[1]);
                    costP[a][b] = sentenceWeight;
                }
            }

            // Convert cost matrix to profit matrix and get optimal assignment.
            profit = munkres.make_cost_matrix(costP);
            optimal = munkres(profit);

            // Calculate the mean paragraph weight from optimal assignment.
            var paragraphWeight = 0;
            n = 0;
            o = 0;
            for (i = 0; i < optimal.length; i++) {
                o = costP[optimal[i][0]][optimal[i][1]];
                if (o >= -1.0) {
                    paragraphWeight += o;
                    n++;
                }
            }
            paragraphWeight = n > 0 ? paragraphWeight / n : 0.0;
            paragraphCost = {matrix: costP, optimal: optimal, weight: paragraphWeight};
            paragraphWeight *= parseFloat(comparisonWeights.sentences);

            return paragraphWeight;
        }

        /**
         * Makes the basic initial graph.
         *
         * @param {function} tickF - The simulation tick function.
         * @param {boolean} doDrag - Flag for using or not node dragging.
         */
        function initGraph(tickF, doDrag) {

            if (graph) {
                simulation.stop();
                graph.remove();
            }

            // The actual graph.
            graph = ddd.select('#graph')
                .append('svg')
                .attr('width', width)
                .attr('height', height)
                .on('click', clearContents);

            // The link force.
            var linkForce = ddd.forceLink(graphData.links)
                .id(function(d) {
                    return d.id;
                })
                .distance(function(d) {
                    var weight = d.weight * -parseInt(nodeDistScale);
                    weight = weight + parseInt(maxNodeDist);
                    return weight;
                });

            simulation = ddd.forceSimulation(graphData.nodes)
                .force("link", linkForce)
                .force("collide", ddd.forceCollide().radius(parseInt(minNodeDist)))
                .force("center", ddd.forceCenter(width / 2, height / 2));

            // The nodes.
            graphNodes = graph.selectAll(".node")
                .data(graphData.nodes)
                .enter().append("circle")
                .attr('class', 'node')
                .attr("r", nodeRadius)
                .on('click', showNodeContent1)
                .on('contextmenu', showNodeContent2)
                .on('mouseover', mouseover)
                .on('mouseout', mouseout);

            if (doDrag) {
                graphNodes.call(ddd.drag()
                                .on('start', dragstarted)
                                .on('drag', dragged)
                                .on('end', dragended));
            }

            // The links.
            graphLinks = graph.selectAll(".link")
                .data(graphData.links)
                .enter().append("line")
                .attr("class", "link")
                .style('stroke', modColours.originalLinks)
                .style("stroke-width", '2px');

            simulation.on('tick', tickF);
        }

        /**
         * Function to add the graphing interface elements to the page.
         */
        function addGraphInterface() {

            var saveGraph = document.getElementById('save-graph');

            // The revert graph button.
            var revertButton = document.createElement('input');
            revertButton.id = 'revert-button';
            revertButton.type = 'button';
            revertButton.value = M.util.get_string('systembutton', 'block_lord');
            revertButton.addEventListener('click', revertGraph);
            revertButton.style.marginRight = '20px';

            saveGraph.appendChild(revertButton);

            // The minimum distance slider.
            var minDistSlider = document.createElement('input');
            minDistSlider.id = 'min-dist-slider';
            minDistSlider.type = 'range';
            minDistSlider.min = '10';
            minDistSlider.max = '50';
            minDistSlider.step = '5';
            minDistSlider.value = minNodeDist;
            minDistSlider.style.width = '100px';
            minDistSlider.addEventListener('change', function() {
                minNodeDist = this.value;
                document.getElementById('min-dist-output').innerHTML = '&nbsp;= ' + this.value;
            });
            saveGraph.appendChild(minDistSlider);

            // The label for the slider.
            var text = document.createTextNode(M.util.get_string('mindistance', 'block_lord'));
            saveGraph.appendChild(text);

            text = document.createElement('label');
            text.id = 'min-dist-output';
            text.innerHTML = '&nbsp;= ' + minDistSlider.value;
            text.style.marginRight = '20px';
            saveGraph.appendChild(text);

            // The maximum distance slider.
            var maxDistSlider = document.createElement('input');
            maxDistSlider.id = 'max-dist-slider';
            maxDistSlider.type = 'range';
            maxDistSlider.min = '50';
            maxDistSlider.max = '500';
            maxDistSlider.step = '25';
            maxDistSlider.value = maxNodeDist;
            maxDistSlider.style.width = '100px';
            maxDistSlider.addEventListener('change', function() {
                maxNodeDist = this.value;
                document.getElementById('max-dist-output').innerHTML = '&nbsp;= ' + this.value;
            });
            saveGraph.appendChild(maxDistSlider);

            // The label for the slider.
            text = document.createTextNode(M.util.get_string('maxdistance', 'block_lord'));
            saveGraph.appendChild(text);

            text = document.createElement('label');
            text.id = 'max-dist-output';
            text.innerHTML = '&nbsp;= ' + maxDistSlider.value;
            text.style.marginRight = '20px';
            saveGraph.appendChild(text);

            // The maximum distance slider.
            var scaleSlider = document.createElement('input');
            scaleSlider.id = 'scale-slider';
            scaleSlider.type = 'range';
            scaleSlider.min = '0';
            scaleSlider.max = '2000';
            scaleSlider.step = '100';
            scaleSlider.value = nodeDistScale;
            scaleSlider.style.width = '100px';
            scaleSlider.addEventListener('change', function() {
                nodeDistScale = this.value;
                document.getElementById('scale-output').innerHTML = '&nbsp;= ' + this.value;
            });
            saveGraph.appendChild(scaleSlider);

            // The label for the slider.
            text = document.createTextNode(M.util.get_string('scalingfactor', 'block_lord'));
            saveGraph.appendChild(text);

            text = document.createElement('label');
            text.id = 'scale-output';
            text.innerHTML = '&nbsp;= ' + scaleSlider.value;
            text.style.marginRight = '20px';
            saveGraph.appendChild(text);

            // The reset graph button.
            var resetButton = document.createElement('input');
            resetButton.type = 'button';
            resetButton.value = M.util.get_string('resetbutton', 'block_lord');
            resetButton.addEventListener('click', resetGraph);
            resetButton.style.marginRight = '20px';

            saveGraph.appendChild(resetButton);
        }

        /**
         * Function to change the graph between system and user generated graphs.
         */
        function revertGraph() {

            if (!usingCustomGraph && Object.keys(presetNodes.usergraph).length > 0) {

                // Set global node distances and scale.
                coordsScale = presetNodes.userscale;
                minNodeDist = presetNodes.usermindist;
                maxNodeDist = presetNodes.usermaxdist;
                nodeDistScale = presetNodes.userdistscale;

                // Make the graph.
                getData(presetNodes.usergraph);
                initGraph(tick1, true);
                usingCustomGraph = true;
                showGraphMessage('usergraph');

                document.getElementById('revert-button').value = M.util.get_string('systembutton', 'block_lord');

            } else {
                // Set global node distances and scale.
                coordsScale = presetNodes.systemscale;
                minNodeDist = presetNodes.systemmindist;
                maxNodeDist = presetNodes.systemmaxdist;
                nodeDistScale = presetNodes.systemdistscale;

                // Make the graph.
                getData(presetNodes.systemgraph);
                initGraph(tick1, true);
                usingCustomGraph = false;
                showGraphMessage('systemgraph');

                document.getElementById('revert-button').value =
                    Object.keys(presetNodes.usergraph).length > 0 ?
                    M.util.get_string('custombutton', 'block_lord') :
                    M.util.get_string('systembutton', 'block_lord');
            }

            // Store last used slider values.
            distanceScales = {
                min: minNodeDist,
                max: maxNodeDist,
                scale: nodeDistScale
            };

            // Display slider values this graph was generated with.
            document.getElementById('min-dist-slider').value = minNodeDist;
            document.getElementById('min-dist-output').innerHTML = '&nbsp;= ' + minNodeDist;
            document.getElementById('max-dist-slider').value = maxNodeDist;
            document.getElementById('max-dist-output').innerHTML = '&nbsp;= ' + maxNodeDist;
            document.getElementById('scale-slider').value = nodeDistScale;
            document.getElementById('scale-output').innerHTML = '&nbsp;= ' + nodeDistScale;
        }

        /**
         * Function to regenerate the system graph.
         */
        function resetGraph() {

            getData({});
            initGraph(tick, false);
            document.getElementById('save-graph').style.display = 'none';

            // Store last used slider values.
            distanceScales = {
                min: minNodeDist,
                max: maxNodeDist,
                scale: nodeDistScale
            };

            // End physics simulation and let user drag nodes.
            setTimeout(function() {
                usingCustomGraph = false;
                sendCoordsToServer(false);
                showGraphMessage('graphsaved');

                graphNodes.call(ddd.drag()
                                .on('start', dragstarted)
                                .on('drag', dragged)
                                .on('end', dragended));

                document.getElementById('save-graph').style.display = 'block';
                document.getElementById('revert-button').value = M.util.get_string('custombutton', 'block_lord');
            }, simulationTimeout);
        }

        /**
         * Simulation tick function for positioning the nodes.
         */
        function tick() {

            var radius = nodeRadius;

            // Keep nodes on screen.
            graphNodes
                .attr("cx", function(d) {
                    d.x = Math.max(radius, Math.min(width - radius, d.x));
                    return d.x;
                })
                .attr("cy", function(d) {
                    d.y = Math.max(radius, Math.min(height - radius, d.y));
                    return d.y;
                })
                .style('fill', function(d) {
                    return d.colour;
                })
                .style('display', function(d) {
                    return d.visible ? 'block' : 'none';
                })
                .raise();

            // Basic link function to move links with nodes.
            graphLinks
                .attr("x1", function(d) {
                    return d.source.x;
                })
                .attr("y1", function(d) {
                    return d.source.y;
                })
                .attr("x2", function(d) {
                    return d.target.x;
                })
                .attr("y2", function(d) {
                    return d.target.y;
                })
                .style("stroke-width", function(d) {
                    return (d.weight * 10) + 'px';
                });
        }

        /**
         * Initial simulation tick function for when graph is rendered statically
         * with coordinate values taken from server database. Adapted from
         * https://stackoverflow.com/questions/28102089/simple-graph-of-nodes-
         * and-links-without-using-force-layout
         */
        function tick1() {

            // Distance and coordinate offsets for scaling to screen space from
            // coordinates stored in server database.
            var xofs = width / 2.0;
            var yofs = height / 2.0;
            var dist = coordsScale;

            // Ensure scale value is okay, reduce if not.
            for (var i = 0, sx, sy; i < graphData.nodes.length; i++) {

                sx = graphData.nodes[i].xcoord * dist + xofs;
                sy = graphData.nodes[i].ycoord * dist + yofs;

                if (sx < 0 || sx > width || sy < 0 || sy > height) {

                    dist *= 0.9;
                    --i;
                }
            }

            // Ensure links are positioned correctly.
            graphLinks
                .attr("x1", function(l) {

                    var sourceNode = graphData.nodes.filter(function(d) {
                        var sid = typeof l.source == 'string' ? l.source : l.source.id;
                        return d.id == sid;
                    })[0];

                    ddd.select(this).attr("y1", (sourceNode.ycoord * dist) + yofs);
                    return (sourceNode.xcoord * dist) + xofs;
                })
                .attr("x2", function(l) {

                    var targetNode = graphData.nodes.filter(function(d) {
                        var tid = typeof l.target == 'string' ? l.target : l.target.id;
                        return d.id == tid;
                    })[0];

                    ddd.select(this).attr("y2", (targetNode.ycoord * dist) + yofs);
                    return (targetNode.xcoord * dist) + xofs;
                })
                .style('display', function(d) {
                    return (!d.source.visible || !d.target.visible) ? 'none' : 'block';
                })
                .style("stroke-width", function(d) {
                    return (d.weight * 10) + 'px';
                });

            // Give nodes the preset coordinates, scaled to current screen.
            graphNodes
                .attr('cx', function(d) {
                    d.x = (d.xcoord * dist) + xofs;
                    return d.x;
                })
                .attr('cy', function(d) {
                    d.y = (d.ycoord * dist) + yofs;
                    return d.y;
                })
                .style('display', function(d) {
                    return (!d.visible) ? 'none' : 'block';
                })
                .style('fill', (function(d) {
                    return d.colour;
                }))
                .raise();
        }

        /**
         * Event listener for mouse hovering over nodes. Shows the text box.
         *
         * @param {object} node - Node that is hovered
         */
        function mouseover(node) {

            ddd.selectAll('.text').remove();
            ddd.selectAll('rect').remove();

            var rtrn = [0],
                up = false,
                left = false,
                right = false;
            var rwidth = 150,
                ifwidth = 304,
                ifheight = 154;
            var txt = node.type + ' ' + node.name;

            // Make the text.
            var t = graph.append('text')
                .attr('class', 'text')
                .attr('id', 't-' + node.id)
                .attr('y', node.y + 32)
                .attr('dy', '.40em')
                .style('pointer-events', 'none')
                .text(txt)
                .call(wrap, rwidth, node.x - (rwidth + 6) / 2 + 8, rtrn);

            // Get rectangle height.
            var rh = rtrn[0] * 16 + 16;

            // If node near bottom of graph area, move text above node.
            if (rh + node.y >= height - ifheight) {
                t.attr('y', height - rh - (height - node.y))
                    .text(txt)
                    .call(wrap, rwidth, node.x - (rwidth + 6) / 2 + 8, rtrn);
                up = true;
            }

            // Move if near right or left edge.
            if (node.x <= ifwidth / 2) {
                t.text(txt).call(wrap, rwidth, node.x + 8, rtrn);
                right = true;
            } else if (node.x >= width - ifwidth / 2) {
                t.text(txt).call(wrap, rwidth, node.x - (rwidth + 6) + 8, rtrn);
                left = true;
            }

            // Make the rectange background.
            var attrX;
            if (right) {
                attrX = node.x;
            } else if (left) {
                attrX = node.x - (rwidth + 6);
            } else {
                attrX = node.x - (rwidth + 6) / 2;
            }

            graph.append('rect')
                .attr('id', 'r-' + node.id)
                .attr('x', attrX)
                .attr('y', up ? height - rh - 16 - (height - node.y) : node.y + 16)
                .attr('width', rwidth + 16)
                .attr('height', rh)
                .style('stroke', 'black')
                .style('fill', 'yellow');

            t.raise();
        }

        /**
         * Called to wrap long text into predefined width.
         * Adapted from https://bl.ocks.org/mbostock/7555321
         *
         * @param {string} text - The text to wrap
         * @param {number} rectWidth - The predefined width
         * @param {number} xOffset - The offset of the x value
         * @param {array} rtrn - Stores the return value
         */
        function wrap(text, rectWidth, xOffset, rtrn) {

            var lineNumber = 0;

            text.each(function() {

                // The variables needed.
                var text = ddd.select(this),
                    words = text.text().split(/\s+/).reverse(),
                    word,
                    line = [],
                    lineHeight = 1.1, // In ems.
                    y = text.attr("y"),
                    dy = parseFloat(text.attr("dy"));

                var tspan = text.text(null)
                    .append("tspan")
                    .attr("x", xOffset)
                    .attr("y", y)
                    .attr("dy", dy + "em");

                // While there are words to wrap.
                word = words.pop();
                while (word) {

                    line.push(word);
                    tspan.text(line.join(" "));

                    // If the length of the line is too long.
                    if (tspan.node().getComputedTextLength() > rectWidth) {

                        line.pop();
                        tspan.text(line.join(" "));

                        line = [word];

                        tspan = text.append("tspan")
                            .attr("x", xOffset)
                            .attr("y", y)
                            .attr("dy", (++lineNumber * lineHeight + dy) + "em")
                            .text(word);
                    }
                    word = words.pop();
                }
            });

            rtrn[0] = ++lineNumber;
        }

        /**
         * Event listener for end of mouse hover. Removes text drawn with mouseover.
         */
        function mouseout() {

            ddd.selectAll('.text').remove();
            ddd.selectAll('rect').remove();
        }

        /**
         * Event listener for dragging nodes during the positioning stage.
         *
         * @param {object} node - The node that is dragged
         */
        function dragstarted(node) {

            // Restart simulation if there is no event.
            if (!ddd.event.active) {
                simulation.on('tick', tick);
                simulation.alphaTarget(0.01).restart();
            }

            node.fx = node.x;
            node.fy = node.y;
        }

        /**
         * Event listener for dragging nodes during positioning stage.
         *
         * @param {object} node - The node that is dragged
         */
        function dragged(node) {

            node.fx = ddd.event.x;
            node.fy = ddd.event.y;
        }

        /**
         * Event listener for dragging nodes.
         *
         * @param {object} node - The node that is dragged
         */
        function dragended(node) {

            node.fx = null;
            node.fy = null;

            // Save graph coordinates and show saved graph message.
            usingCustomGraph = true;
            sendCoordsToServer(true);
            document.getElementById('revert-button').value = M.util.get_string('systembutton', 'block_lord');
            showGraphMessage('graphsaved');
        }

        /**
         * Function to show a graph message.
         *
         * @param {string} strid - The string id.
         */
        function showGraphMessage(strid) {

            var saveGraph = document.getElementById('save-graph');
            var label = document.createElement('label');
            label.innerHTML = M.util.get_string(strid, 'block_lord');
            saveGraph.appendChild(label);

            setTimeout(function() {
                label.remove();
            }, 2000);
        }

        /**
         * Event listener for clearing module content and similarity.
         */
        function clearContents() {

            var nl = document.getElementById('node-content-left');
            nl.innerHTML = '&nbsp';

            var nr = document.getElementById('node-content-right');
            nr.innerHTML = '&nbsp';

            var s = document.getElementById('similarity-score');
            s.innerHTML = '&nbsp';

            var sm = document.getElementById('similarity-matrix');
            sm.innerHTML = '&nbsp';

            node1 = undefined;
            node2 = undefined;
        }

        /**
         * Event listener for showing module content with left click.
         *
         * @param {object} node - The graph node object.
         */
        function showNodeContent1(node) {

            // Keep click from bubbling to graph, which clears the contents.
            ddd.event.stopPropagation();

            // Don't show the same content again.
            if (node.id == node2) {
                return;
            }

            node1 = node.id;

            showNodeContents(node, 'node-content-left');
        }

        /**
         * Event listener for showing module content with right click.
         *
         * @param {object} node - The graph node object.
         */
        function showNodeContent2(node) {

            // Prevent regular right click menu.
            ddd.event.preventDefault();

            // Don't show same content again.
            if (node.id == node1) {
                return;
            }

            node2 = node.id;

            showNodeContents(node, 'node-content-right');
        }

        /**
         * Shows module content.
         *
         * @param {object} node - The graph node object.
         * @param {string} elementId - The element to add the contents to.
         */
        function showNodeContents(node, elementId) {

            var contents = '<b>' + M.util.get_string('moduleid', 'block_lord') + ': ' + node.id + '<br>' +
                M.util.get_string('name', 'block_lord') + ': </b>' + names[node.id].name + '<br><b>' +
                M.util.get_string('intro', 'block_lord') + ': </b>' + names[node.id].intro + '<br>';

            if (paragraphs[node.id]) {
                var i = 0;
                paragraphs[node.id].forEach(function(p) {
                    contents += '<b>P' + i + ': </b>' + p + '<br>';
                    i++;
                });
            }

            var e = document.getElementById(elementId);
            e.innerHTML = contents;

            showSimilarity();
        }

        /**
         * Shows the similarity value between modules.
         */
        function showSimilarity() {

            // Don't have a similarity unless both nodes shown.
            if (!node1 || !node2) {
                return;
            }

            var s = document.getElementById('similarity-score');
            var key = parseInt(node1) < parseInt(node2) ? node1 + '_' + node2 : node2 + '_' + node1;

            // Show similarity message. Could be value, error, or not complete.
            if (Object.keys(weights[key]).length > 0) {
                var weight = getWeight(key);

                if (weight == 0.0) {
                    s.innerHTML = M.util.get_string('comparisonerror', 'block_lord');

                } else {
                    s.innerHTML = M.util.get_string('similaritystr', 'block_lord') + ' ' + weight;
                    showSimilarityMatrix(key);
                }
            } else {
                s.innerHTML = M.util.get_string('notcalculated', 'block_lord');
            }
        }

        /**
         * Called to render the similarity matrix for 2 modules.
         *
         * @param {string} key - The key into the matrices array
         */
        function showSimilarityMatrix(key) {

            var sm = document.getElementById('similarity-matrix');
            sm.innerHTML = '&nbsp;';
            var keys = key.split('_');

            // Sorting function for comparisons.
            var sorter = function(a, b) {
                if (a < b) {
                    return 1;
                } else if (a > b) {
                    return -1;
                } else {
                    return 0;
                }
            };

            // Show matrices for all comparisons.
            if (Object.keys(matrices[key]).length > 0) {

                var header, ps, ss, pkey0, skey0, pkey1, skey1, sent0, sent1;
                var matrixKeys = Object.keys(matrices[key]).sort(sorter);
                var shownIntroCost = false,
                    shownParagraphCost = false;
                var head1,
                    head2 = M.util.get_string('optimalassign', 'block_lord') + ':';

                for (var i = 0, m; i < matrixKeys.length; i++) {
                    m = matrixKeys[i];

                    if (m == 'name') { // Show names comparison.
                        header = M.util.get_string('names', 'block_lord');
                        sent0 = names[keys[0]].cname;
                        sent1 = names[keys[1]].cname;

                    } else if (m.startsWith('intro')) { // Show intros comparison.
                        if (!shownIntroCost) {
                            head1 = M.util.get_string('introscost', 'block_lord') + ' ' + keys[1] + ' x ' + keys[0];
                            showCostMatrix(head1, head2, sm, introCost);
                            shownIntroCost = true;
                        }

                        ss = m.slice(5).split('x');
                        header = M.util.get_string('intros', 'block_lord') + ' ' +
                            keys[1] + ' S' + ss[1] + ' x ' + keys[0] + ' S' + ss[0];
                        sent0 = names[keys[0]].cintro[ss[0]];
                        sent1 = names[keys[1]].cintro[ss[1]];

                    } else { // Show paragraphs comparison.
                        if (!shownParagraphCost) {
                            head1 = M.util.get_string('parascost', 'block_lord') + ' ' + keys[1] + ' x ' + keys[0];
                            showCostMatrix(head1, head2, sm, paragraphCost);

                            for (var pkey in sentenceCost) {
                                ss = pkey.split('_');
                                head1 = M.util.get_string('sentscost', 'block_lord') + ' ' +
                                    keys[1] + ' P' + ss[1] + ' x ' + keys[0] + ' P' + ss[0];
                                showCostMatrix(head1, head2, sm, sentenceCost[pkey]);
                            }
                            shownParagraphCost = true;
                        }

                        // Split compared key into paragraph and sentence indices.
                        ps = m.split('P');
                        ss = ps[1].split('S');
                        pkey0 = parseInt(ss[0]);
                        skey0 = parseInt(ss[1]);

                        ss = ps[2].split('S');
                        pkey1 = parseInt(ss[0]);
                        skey1 = parseInt(ss[1]);

                        header = keys[1] + ' P' + pkey1 + 'S' + skey1 +
                            ' x ' + keys[0] + ' P' + pkey0 + 'S' + skey0;
                        sent0 = sentences[keys[0]][pkey0][skey0];
                        sent1 = sentences[keys[1]][pkey1][skey1];
                    }

                    showMatrix(header, sm, sent1, sent0, matrices[key][m], weights[key][m]);
                }
            }
        }

        /**
         * Called to render the similarity matrix for a module.
         *
         * @param {string} head1 - The cost matrix header.
         * @param {string} head2 - The optimal assignment header.
         * @param {HTMLElement} element - The element to attach to.
         * @param {object} cost - The cost matrix and optimal assignment to show.
         */
        function showCostMatrix(head1, head2, element, cost) {

            // Initialize the table.
            var table = document.createElement('table');
            var row = table.insertRow();
            var cell = row.insertCell();

            // Show the heading with similarity score.
            var text = document.createTextNode(head1);
            cell.appendChild(text);
            cell.appendChild(document.createElement('br'));

            var scr = document.createTextNode('   ' + cost.weight);
            cell.appendChild(scr);
            cell.style.fontWeight = 'bold';

            // Do the top row of the table with indices.
            var i = 0;
            for (i = 0; i < cost.matrix.length; i++) {
                cell = row.insertCell();
                text = document.createTextNode(i);
                cell.appendChild(text);
            }

            // Do remaining rows, starting with an index.
            for (i = 0; i < cost.matrix.length; i++) {
                row = table.insertRow();
                cell = row.insertCell();
                text = document.createTextNode(i);
                cell.appendChild(text);

                for (var j = 0; j < cost.matrix[0].length; j++) {
                    cell = row.insertCell();
                    text = document.createTextNode(cost.matrix[i][j]);
                    cell.appendChild(text);
                }
            }

            // Add table to page.
            element.appendChild(table);

            // Add optimal assignment.
            table = document.createElement('table');
            row = table.insertRow();
            cell = row.insertCell();

            // Show the heading.
            text = document.createTextNode(head2);
            cell.appendChild(text);
            cell.style.fontWeight = 'bold';

            // Do the top row of the table with indices.
            for (i = 0; i < cost.optimal.length; i++) {
                cell = row.insertCell();
                text = document.createTextNode(cost.optimal[i]);
                cell.appendChild(text);
            }

            element.appendChild(table);
            element.appendChild(document.createElement('hr'));
        }

        /**
         * Called to render the similarity matrix for a module.
         *
         * @param {string} head - The header for the table.
         * @param {HTMLElement} element - The element to attach to.
         * @param {string} text0 - The first sentence.
         * @param {string} text1 - The second sentence.
         * @param {array} matrix - The similarity matrix.
         * @param {string} score - The similarity score.
         */
        function showMatrix(head, element, text0, text1, matrix, score) {

            // Initialize the table.
            var table = document.createElement('table');
            var row = table.insertRow();
            var cell = row.insertCell();

            // Show the heading with similarity score.
            var text = document.createTextNode(head);
            cell.appendChild(text);
            cell.appendChild(document.createElement('br'));

            var scr = document.createTextNode('   ' + score);
            cell.appendChild(scr);
            cell.style.fontWeight = 'bold';

            // Martix is null when no words in a sentence in Wordnet.
            if (!matrix) {
                element.appendChild(table);
                element.appendChild(document.createElement('hr'));
                return;
            }

            // Do the top row of the table with the first sentence.
            var split = text0.split(' ');
            for (var i = 0; i < matrix[0].length; i++) {
                cell = row.insertCell();
                text = document.createTextNode(split[i]);
                cell.appendChild(text);
            }

            // Do the remaining rows.
            split = text1.split(' ');
            var j = 0;
            matrix.forEach(function(mr) {
                row = table.insertRow();
                cell = row.insertCell();

                // The words in the second sentence.
                text = document.createTextNode(split[j++]);
                cell.appendChild(text);

                // The matrix similarity values.
                mr.forEach(function(mc) {
                    cell = row.insertCell();
                    text = document.createTextNode(mc.toFixed(6));
                    cell.appendChild(text);
                });
            });

            // Add table to page.
            element.appendChild(table);
            element.appendChild(document.createElement('hr'));
        }

        /**
         * Called to send the module node coordinates to the server. This will also
         * update other arrays used in conjunction with the researcher interface.
         *
         * @param {boolean} isCustom - Flag indicates whether the graph is user generated.
         */
        function sendCoordsToServer(isCustom) {

            simulation.stop();

            // Get normalized coordinates.
            var normalized = normalizeNodes();

            // Set the last changed time stamp.
            normalized.time = Date.now();
            normalized.iscustom = isCustom ? 1 : 0;

            // Align client side data with server side.
            if (isCustom) {
                presetNodes.userscale = normalized.scale;
                presetNodes.usergraph = normalized.nodes;
                presetNodes.usermindist = distanceScales.min;
                presetNodes.usermaxdist = distanceScales.max;
                presetNodes.userdistscale = distanceScales.scale;

                normalized.mindist = distanceScales.min;
                normalized.maxdist = distanceScales.max;
                normalized.distscale = distanceScales.scale;

            } else {
                presetNodes.systemscale = normalized.scale;
                presetNodes.systemgraph = normalized.nodes;
                presetNodes.systemmindist = minNodeDist;
                presetNodes.systemmaxdist = maxNodeDist;
                presetNodes.systemdistscale = nodeDistScale;

                normalized.mindist = minNodeDist;
                normalized.maxdist = maxNodeDist;
                normalized.distscale = nodeDistScale;
            }

            // Update the nodes at the server.
            callServer(coordsScript, normalized);
        }

        /**
         * Called to normalize the coordinates of the module nodes.
         *
         * @return {object}
         */
        function normalizeNodes() {

            var normalized = {};
            var dx,
                dy,
                d,
                max = 0,
                cx = width / 2,
                cy = height / 2;

            // Find node with greatest distance from centre.
            graphData.nodes.forEach(function(dn) {

                dx = dn.x - cx;
                dy = dn.y - cy;
                d = Math.sqrt(dx * dx + dy * dy);

                if (d > max) {
                    max = d;
                }
            });

            // Store distance and node that was used.
            normalized.scale = max;
            normalized.links = graphData.links;
            normalized.nodes = {};

            // Normalize all nodes based on greatest distance.
            graphData.nodes.forEach(function(dn) {

                normalized.nodes[dn.id] = {
                    'xcoord': '' + ((dn.x - cx) / max),
                    'ycoord': '' + ((dn.y - cy) / max)
                };
            });

            return normalized;
        }

        /**
         * Function called to send data to server.
         *
         * @param {string} url The name of the file receiving the data
         * @param {object} outData The data to send to the server
         */
        function callServer(url, outData) {

            var req = new XMLHttpRequest();
            req.open('POST', url);
            req.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

            req.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    // A console.log(this.responseText);
                }
            };
            req.send('cid=' + courseId + '&data=' + JSON.stringify(outData) +
                     '&sesskey=' + sessionKey);
        }

        // End of modular encapsulation, start the program.
        init(incoming);
    };
    return lord;
});
