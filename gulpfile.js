var plugin = 'backstage',

	gulp 		= require('gulp'),
	plugins 	= require('gulp-load-plugins' )(),
	exec 		= require('gulp-exec'),
	concat 		= require('gulp-concat'),
	notify 		= require('gulp-notify'),
	chmod 		= require('gulp-chmod'),
	fs          = require('fs'),
	del         = require('del'),
	rsync 		= require('gulp-rsync'),
	replace 	= require('gulp-replace'),
	rename 		= require('gulp-rename');

require('es6-promise').polyfill();

var options = {
	silent: true,
	continueOnError: true // default: false
};

/**
 * Copy plugin folder outside in a build folder, recreate styles before that
 */
gulp.task( 'copy-folder', function() {
	var dir = process.cwd();
	return gulp.src( './*' )
		.pipe( plugins.exec( 'rm -Rf ./../build; mkdir -p ./../build/' + plugin + ';', {
			silent: true,
			continueOnError: true // default: false
		} ) )
		.pipe(rsync({
			root: dir,
			destination: '../build/' + plugin + '/',
			// archive: true,
			progress: false,
			silent: false,
			compress: false,
			recursive: true,
			emptyDirectories: true,
			clean: true,
			exclude: ['node_modules']
		}));
} );

/**
 * Clean the folder of unneeded files and folders
 */
gulp.task( 'build', ['copy-folder'], function() {

	// files that should not be present in build zip
	var files_to_remove = [
		'node_modules',
		'bin',
		'tests',
		'.travis.yml',
		'.babelrc',
		'.gitignore',
		'.codeclimate.yml',
		'.csslintrc',
		'.eslintignore',
		'.eslintrc',
		'circle.yml',
		'phpunit.xml.dist',
		'.sass-cache',
		'config.rb',
		'gulpfile.js',
		'webpack.config.js',
		'package.json',
		'package-lock.json',
		'pxg.json',
		'build',
		'.idea',
		'**/*.css.map',
		'**/.git*',
		'*.sublime-project',
		'.DS_Store',
		'**/.DS_Store',
		'__MACOSX',
		'**/__MACOSX',
		'.csscomb',
		'.csscomb.json',
		'.codeclimate.yml',
		'tests',
		'circle.yml',
		'.circleci',
		'.labels',
		'.jscsrc',
		'.jshintignore',
		'browserslist',
		'socket/node_modules',
		'socket/src',
		'socket/scss',
		'socket/.babelrc',
		'socket/gulpfile.js',
		'socket/package.json',
		'socket/semantic.json',
        'README.md',
	];

	files_to_remove.forEach( function( e, k ) {
		files_to_remove[k] = '../build/' + plugin + '/' + e;
	} );

	del.sync(files_to_remove, {force: true});
} );

/**
 * Create a zip archive out of the cleaned folder and delete the folder
 */
gulp.task( 'zip', ['build'], function() {
	var versionString = '';
	// get plugin version from the main plugin file
	var contents = fs.readFileSync("./" + plugin + ".php", "utf8");

	// split it by lines
	var lines = contents.split(/[\r\n]/);

	function checkIfVersionLine(value, index, ar) {
		var myRegEx = /^[\s\*]*[Vv]ersion:/;
		if (myRegEx.test(value)) {
			return true;
		}
		return false;
	}

	// apply the filter
	var versionLine = lines.filter(checkIfVersionLine);

	versionString = versionLine[0].replace(/^[\s\*]*[Vv]ersion:/, '').trim();
	versionString = '-' + versionString.replace(/\./g, '-');

	return gulp.src('./')
		.pipe(exec('cd ./../; rm -rf ' + plugin[0].toUpperCase() + plugin.slice(1) + '*.zip; cd ./build/; zip -r -X ./../' + plugin[0].toUpperCase() + plugin.slice(1) + versionString + '.zip ./; cd ./../; rm -rf build'));

} );
