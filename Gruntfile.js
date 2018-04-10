module.exports = function( grunt ) {

	'use strict';

	// Project configuration
	grunt.initConfig( {

		pkg: grunt.file.readJSON( 'package.json' ),

		addtextdomain: {
			options: {
				textdomain: 'ghcp',
			},
			update_all_domains: {
				options: {
					updateDomains: true
				},
				src: [ '*.php', '**/*.php', '!\.git/**/*', '!bin/**/*', '!node_modules/**/*', '!tests/**/*' ]
			}
		},

		wp_readme_to_markdown: {
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
					mainFile: 'ghcp.php',
					potFilename: 'ghcp.pot',
					potHeaders: {
						poedit: true,
						'x-poedit-keywordslist': true
					},
					type: 'wp-plugin',
					updateTimestamp: true
				}
			}
		},

		postcss: {
			default: {
                options: {
                    processors: [
                        require('cssnano')
                    ]
                },
                files: {
                    'assets/github-light.min.css': 'node_modules/github-syntax-light/lib/github-light.css'
                }
            },
			none: {
                files: {
                    'assets/github-light.css': 'node_modules/github-syntax-light/lib/github-light.css'
                }
			}
		}
	} );

	grunt.loadNpmTasks( 'grunt-wp-i18n' );
	grunt.loadNpmTasks( 'grunt-postcss' );
	grunt.loadNpmTasks( 'grunt-wp-readme-to-markdown' );
	grunt.registerTask( 'default', [ 'i18n','readme', 'postcss' ] );
	grunt.registerTask( 'i18n', ['addtextdomain', 'makepot'] );
	grunt.registerTask( 'readme', ['wp_readme_to_markdown'] );

	grunt.util.linefeed = '\n';

};
