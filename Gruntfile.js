module.exports = function(grunt) {

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        // Throw all our JS-files into one
        concat: {
            options: {
                separator: ';'
            },
            dist: {
                src: ['public/js/zettlr.editor.js',
                'public/js/zettlr.helper.js',
                'public/js/zettlr.media-library.js'],
                dest: 'public/js/<%= pkg.name %>.js'
            }
        },

        // Minify them
        uglify: {
            options: {
                banner: '/* <%= pkg.name %> <%= grunt.template.today("yyyy-mm-dd") %> */\n'
            },
            dist: {
                files: {
                    'public/js/<%= pkg.name %>.min.js' : ['<%= concat.dist.dest %>']
                }
            }
        },

        // Use JSHint for validating the file
        jshint: {
            files: ['Gruntfile.js', 'public/js/zettlr.*.js'],
            options: {
                globals: {
                    jQuery: true,
                    console: true,
                    module: true,
                    document: true
                },
                // We need multistr for our Medialibrary, as it also brings HTML with line escapings (\)
                // TODO: Will be removed in next jshint release, so then we can remove it here as well
                multistr: true
            }
        },

        // Watchdog to watch for changes
        watch: {
            files: ['<%= jshint.files %>'],
            tasks: ['jshint']
        }
    });

    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-jshint');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-concat');

    // Register default task
    grunt.registerTask('build-contenttools', function() {
        var cb = this.async();
        var child = grunt.util.spawn({
            grunt: true,
            args: ['build'], // Run the build task
            opts: {
                cwd: 'node_modules/ContentTools' // Working dir: ContentTools
            }
        }, function(error, result, code) {
            cb(); // This just means that the command will be issued async, letting us pipe the stdout in real time
        });

        child.stdout.pipe(process.stdout);
        child.stderr.pipe(process.stderr);
    });

    grunt.registerTask('default', ['jshint', 'concat', 'uglify']);
    grunt.registerTask('build', ['default', 'build-contenttools']);
};
