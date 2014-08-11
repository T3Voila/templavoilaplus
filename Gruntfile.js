module.exports = function(grunt) {

	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		banner: '/*! <%= pkg.title || pkg.name %> v<%= pkg.version %> ' +
			'<%= pkg.homepage ? "(" + pkg.homepage + ")\\n" : "" %>' +
			' * Copyright (c) <%= grunt.template.today("yyyy") %> <%= pkg.author.name %> <<%= pkg.author.email %>> (<%= pkg.author.url %>) \n' +
			' */\n',

		compass: {
			options: {
				basePath: 'Resources/Private/Sass/',
				config: 'Resources/Private/Sass/config.rb',
				environment: 'development',
				outputStyle: 'expanded',
				noLineComments: false
			},
			compile: {}
		},

		clean: {
			compass: [
				'Resources/Private/Sass/.sass-cache/',
				'Resources/Public/Image/_Generated/**/*/',
				'Resources/Public/StyleSheet/**/*.css',
				'Resources/Public/StyleSheet/*.css',
			]
		},

		watch: {
			options: {
				interrupt: true
			},
			configFiles: {
				files: ['Gruntfile.js'],
				options: {
					reload: true
				}
			},
			compass_files_changed: {
				files: [
					'Resources/Private/Sass/**/*.scss',
					'Resources/Public/Image/**/Sprites/**/*.png',
					'Resources/Public/Image/**/Sprites/*.png'
				],
				tasks: ['compass:compile'],
				options: {
					event: ['changed']
				}
			},
			compass_files_added_deleted: {
				files: [
					'Resources/Private/Sass/**/*.scss',
					'Resources/Public/Image/**/Sprites/**/*.png',
					'Resources/Public/Image/**/Sprites/*.png'
				],
				tasks: ['clean:compass', 'compass:compile'],
				options: {
					event: ['added', 'deleted'],
					interrupt: false
				}
			}
		}
	});

	grunt.loadNpmTasks('grunt-contrib-compass');
	grunt.loadNpmTasks('grunt-contrib-clean');
	grunt.loadNpmTasks('grunt-contrib-watch');

	grunt.registerTask('default', ['build']);
	grunt.registerTask('build', ['clean', 'compass']);
};