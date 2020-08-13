The D3 library is available from https://d3js.org. The version used in the
block_behaviour plugin is 5.8.2, although at the time of this writing the latest
version is 5.16.0. In either case, the library is already included with this
plugin and nothing more needs to be done.

To upgrade to the latest version (which may or may not break existing code),
download the code from https://d3js.org and place it in this directory,
overwriting the current version. Then copy the d3.js file into the amd/src
directory. The d3.min.js file can be copied into the amd/build directory or
grunt can be run to minify the amd/src file. Finally, update the
lib/thirdpartylibs.xml file with the latest version number.
