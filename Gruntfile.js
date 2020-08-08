/*global module:false*/
module.exports = function(grunt) {

    // We need to include the core Moodle grunt file too, otherwise we can't run tasks like "amd".
    require("grunt-load-gruntfile")(grunt);
    grunt.loadGruntfile("../../Gruntfile.js");

    // These plugins provide necessary tasks.
    grunt.loadNpmTasks('grunt-contrib-jshint');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-clean');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-less');
    grunt.loadNpmTasks('grunt-babel');

    /**
     * Function to generate the destination for the uglify task
     * (e.g. build/file.min.js). This function will be passed to
     * the rename property of files array when building dynamically:
     * http://gruntjs.com/configuring-tasks#building-the-files-object-dynamically
     *
     * @param {String} destPath the current destination
     * @param {String} srcPath the  matched src path
     * @return {String} The rewritten destination path.
     */
    var uglifyRename = function(destPath, srcPath) {
        destPath = srcPath.replace('src', 'build');
        destPath = destPath.replace('.js', '.min.js');
        return destPath;
    };

    // Project configuration.
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        jshint: {
            files: ['Gruntfile.js', 'javascript/*.js', 'amd/src/lord.js'/*, 'amd/src/modules.js'*/],
            options: {
                globals: {
                    jQuery: true
                }
            }
        },
        watch: {
            files: ['<%= jshint.files %>'],
            tasks: ['jshint', 'eslint']
        },
        eslint: {
            // Even though warnings dont stop the build we don't display warnings by default because
            // at this moment we've got too many core warnings.
            options: {
                quiet: grunt.option('show-lint-warnings')
            },
            amd: {
                src: ['<%= jshint.files %>']
            },
        },
        clean: ['amd/build/*.min.js'],
        uglify: {
            amd: {
                files: [{
                    expand: true,
                    src: 'amd/src/*.js',
                    rename: uglifyRename
                }],
                options: {
                    report: 'none',
                    banner: '/*! <%= pkg.name %> v<%= pkg.version %> <%= grunt.template.today("dd-mm-yyyy") %> */\n'
                }
            },
        },
        babel: {
            options: {
                sourceMap: true,
                presets: ['@babel/preset-env']
            },
            dist: {
                files: ''
            }
        },
        stylelint: {
            css: [''],
            less: ['']
        },
        less: {
            // Production config is also available.
            development: {
                options: {
                    // Specifies directories to scan for @import directives when parsing.
                    // Default value is the directory of the source, which is probably what you want.
                    paths: ["less/"],
                    compress: true
                },
                files: {
                    "styles.css": "less/styles.less"
                }
            },
        }
    });

    // Default task.
    grunt.registerTask('default', ['watch']);
};
