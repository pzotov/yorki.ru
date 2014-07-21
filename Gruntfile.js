module.exports = function (grunt) {
    grunt.initConfig({
        autoprefixer: {
            dist: {
                files: {
                    'assets/css/styles.min.css': 'assets/css/styles.css'
                }
            }
        },
		cssmin: {
			options: {
			  banner: '/* \n' +
'* http://zotov.info/\n'+
'*\n'+
'* Верстка и настройка cms Павел Зотов\n'+
'* mailto: pavel.v.zotov@gmail.com\n'+
'* http://zotov.info/\n'+
'*/\n'
			},
			combine: {
				files: {
					'assets/css/styles.min.css': [
						'assets/css/reset.css',
						'assets/css/styles.min.css',
						'assets/css/jquery.fancybox.css',
						'assets/css/forms.css',
						'assets/css/content.css',
						'assets/css/medias.css'
					]
				}
			}
		},
        watch: {
            css: {
                files: [
					'assets/css/styles.css',
					'assets/css/jquery.fancybox.css',
					'assets/css/content.css',
					'assets/css/medias.css',
					'assets/css/forms.css'
					],
                tasks: ['autoprefixer','cssmin','ftp_push:css'],
				options: {
	    		  spawn: false,
		    	},
            },
            js: {
                files: [
					'assets/js/jquery.js',
	                'assets/js/jquery-ui.min.js',
	                'assets/js/jquery.fancybox.js',
	                'assets/js/svg.js',
					'assets/js/s.js'
					],
                tasks: ['uglify','ftp_push:js'],
				options: {
	    		  spawn: false,
		    	},
            }
        },
		uglify: {
			main: {
				files: {
					'assets/js/s.min.js': [
						'assets/js/jquery.js',
						'assets/js/jquery-ui.min.js',
						'assets/js/jquery.fancybox.js',
						'assets/js/svg.js',
						'assets/js/s.js'
					]
				}
			}
		},
		ftp_push: {
			css:{
				options: {
					authKey: "w74.ru",
					host: "w74.ru",
					dest: "/www/yorki.w74.ru/assets/",
					port: 21
				},
				files: [{
						expand:true,
						cwd:"",
						src:["assets/css/styles.min.css"]
						}]
			},
			js:{
				options: {
					authKey: "w74.ru",
					host: "w74.ru",
					dest: "/www/yorki.w74.ru/assets/",
					port: 21
				},
				files: [{
						expand:false,
						cwd:"",
						src:["assets/js/s.min.js"]
						}]
			}
		}
    });
    grunt.loadNpmTasks('grunt-autoprefixer');
    grunt.loadNpmTasks('grunt-contrib-cssmin');
    grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-ftp-push');
};