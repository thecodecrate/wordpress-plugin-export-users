module.exports = function( grunt ) {

	'use strict';

	// Project configuration
	grunt.initConfig( {

		pkg: grunt.file.readJSON( 'package.json' ),

		addtextdomain: {
			options: {
				textdomain: 'user-export-with-their-meta',
			},
			update_all_domains: {
				options: {
					updateDomains: true
				},
				src: [ '*.php', '**/*.php', '!\.git/**/*', '!bin/**/*', '!node_modules/**/*', '!tests/**/*' ]
			}
		},

		wp_readme_to_markdown: {
			options: {
				screenshot_url: '/assets/{screenshot}.png'
			},
			your_target: {
				files: {
					'README.md': 'readme.txt'
				}
			},
		},

		makepot: {
			target: {
				options: {
					domainPath: '/languages',
					exclude: [ '\.git/*', 'bin/*', 'node_modules/*', 'tests/*' ],
					mainFile: 'user-export-with-their-meta.php',
					potFilename: 'user-export-with-their-meta.pot',
					potHeaders: {
						poedit: true,
						'x-poedit-keywordslist': true
					},
					type: 'wp-plugin',
					updateTimestamp: true
				}
			}
		},

		file_append: {
			default_options: {
			  files: [
				{
				  prepend: "![WordPress Plugin: Tested WP Version](https://img.shields.io/wordpress/plugin/tested/user-export-with-their-meta-data) ![WordPress Plugin Active Installs](https://img.shields.io/wordpress/plugin/installs/user-export-with-their-meta-data) [![Actions Status](https://github.com/loureirorg/wordpress-plugin-export-users/workflows/Deploy%20to%20WordPress.org/badge.svg?tag=latest)](https://github.com/loureirorg/wordpress-plugin-export-users/actions)\n\n",
				  input: 'README.md',
				}
			  ]
			}
		  }
	} );

	grunt.loadNpmTasks( 'grunt-wp-i18n' );
	grunt.loadNpmTasks( 'grunt-wp-readme-to-markdown' );
	grunt.loadNpmTasks( 'grunt-file-append' );
	grunt.registerTask( 'default', [ 'i18n','readme' ] );
	grunt.registerTask( 'i18n', ['addtextdomain', 'makepot'] );
	grunt.registerTask( 'readme', ['wp_readme_to_markdown', 'file_append'] );

	grunt.util.linefeed = '\n';

};
