(function(){
		'use strict';

		var gulp = require('gulp');
		var sourcemaps = require('gulp-sourcemaps');
		var sass = require('gulp-sass');
		var sassLint = require('gulp-sass-lint');
		var eslint = require('gulp-eslint');
		var autoprefixer = require('gulp-autoprefixer');
		var concat = require('gulp-concat');
		var gcmq = require('gulp-group-css-media-queries');
		var uglify = require('gulp-uglify');
		var del = require('del');
		var nano = require('gulp-cssnano');
		var order = require("gulp-order");
		var server = require('gulp-server-livereload');

/*-----------------------------------------------------------------------------*/
/*-----------------------    PATHS END OPTIONS     ----------------------------*/
/*↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓*/
		var paths = {};
		paths = {
			themeFolder: './wp-content/themes/podoba/',
			src: {
				css: './wp-content/themes/podoba/src/css/**',/*TODO path to sass*/
				js: './wp-content/themes/podoba/src/js/**/*.js',/*TODO path to js*/
			},
			build: {
				css: './wp-content/themes/podoba/',
				js: './wp-content/themes/podoba/assets/js/',
			},
			sassLint: ['./src/css/**/*.scss', '!./src/css/vendors/**', '!./src/css/base/**'],/*TODO path to lint. ! to exclude file*/
			esLint: ['./src/js/**', '!./src/js/vendors/**'],/*TODO path to lint. ! to exclude file*/
		};

		var options = {
				sass: {
					errLogToConsole: true,
					// outputStyle: 'compressed'
				},
				autoprefixer: {
					browsers: ['last 5 versions']
				},
				nano : {
					zindex: false
				},
				sassLint : {
					rules: {
						'indentation' : [1, {'size' : 'tab'}],
						'property-sort-order' : 0,
						'no-color-literals' : 0,
						'no-mergeable-selectors': 0,
						'mixins-before-declarations': 0,
						'force-pseudo-nesting': 0,
						'placeholder-in-extend': 0,
					},
				},
		};
/*↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑*/
/*-----------------------    PATHS END OPTIONS     ----------------------------*/
/*-----------------------------------------------------------------------------*/

/*-----------------------------------------------------------------------------*/
/*-----------------------        CSS PART          ----------------------------*/
/*↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓*/
	function cleanCSSTask(){
			return del(['./wp-content/themes/podoba/style.css']);
	}

	function cssTask(){
		gulp.src(paths.src.css)
			.pipe(sourcemaps.init())
			.pipe(sass(options.sass).on('error', sass.logError))
			.pipe(autoprefixer(options.autoprefixer))
			.pipe(concat('style.css'))
			.pipe(gcmq())/*turn off if sourcemaps needed*/
			.pipe(nano(options.nano))
			.pipe(sourcemaps.write('/'))
			.pipe(gulp.dest(paths.build.css));
	}

	function sassLintTask(){
		gulp.src(paths.sassLint)
			.pipe(sassLint(options.sassLint))
			.pipe(sassLint.format())
			.pipe(sassLint.failOnError());
	}
/*↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑*/
/*-----------------------        CSS PART          ----------------------------*/
/*-----------------------------------------------------------------------------*/

/*-----------------------------------------------------------------------------*/
/*-----------------------         JS PART          ----------------------------*/
/*↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓*/
		function cleanJSTask(){
				return del(['./js/main.min.js']);
		}

		function jsTask(){
			gulp.src(paths.src.js)
				.pipe(order([
					'vendors/jquery-3.1.1.min.js',/*TODO set order. You can put CSS libes in the start to concatenate they with other css*/
					'vendors/**',
				]))
				.pipe(concat('global.js'))
				.pipe(uglify().on('error', function(e){
					console.log(e);
				}))
				.pipe(gulp.dest(paths.build.js));
		}

		function esLintTask(){
			gulp.src(paths.esLint)
				.pipe(eslint())
				.pipe(eslint.format())
				//.pipe(eslint.failAfterError());
		}
/*↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑*/
/*-----------------------         JS PART          ----------------------------*/
/*-----------------------------------------------------------------------------*/

/*-----------------------------------------------------------------------------*/
/*-----------------------     Livereload PART      ----------------------------*/
/*↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓*/
	function startServer(){
		gulp.src('./')
			.pipe(server({
				livereload: {
					enable: true,
					filter: function(filePath, cb) {
						cb( (/(32334\\css\\)|(32334\\js\\)|(32334\\index\.html)/.test(filePath)) );/*TODO set folders to watch*/
					}
				},
				defaultFile: 'index.html',
				open: true
			}));
	}
/*↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑*/
/*-----------------------     Livereload PART      ----------------------------*/
/*-----------------------------------------------------------------------------*/

	function defaultTask(){
		gulp.watch(paths.src.css, ['css', 'sasslint']);
		gulp.watch(paths.src.js, ['js', 'eslint']);
	}

	gulp.task('clean-css', cleanCSSTask);
	gulp.task('css', ['clean-css'], cssTask);
	gulp.task('sasslint', sassLintTask);

	gulp.task('eslint', esLintTask);
	gulp.task('clean-js', cleanJSTask);
	gulp.task('js', ['clean-js'], jsTask);

	gulp.task('webserver', startServer);

	gulp.task('livereload', ['css', 'js', 'sasslint', 'eslint', 'webserver'], defaultTask);
	gulp.task('default', ['css', 'js', 'sasslint', 'eslint'], defaultTask);

})();
