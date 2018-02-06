/*!
 * gulp
 gulp-minify-css
gulp-livereload 

$ npm install gulp gulp-sass gulp-autoprefixer gulp-clean gulp-jshint gulp-uglify gulp-rename gulp-clean-css --save-dev
 */

// Load plugins
var gulp = require('gulp'),
    scss = require('gulp-sass'),
    path = require('path'),
    autoprefixer = require('gulp-autoprefixer'),
    cleancss = require('gulp-clean-css'),
    jshint = require('gulp-jshint'),
    uglify = require('gulp-uglify'),
    rename = require('gulp-rename'),
    clean = require('gulp-clean');

// Styles
gulp.task('styles', function() {
  return gulp.src('scss/bloom.scss')
    .pipe(scss({
      paths: [ path.join(__dirname, 'scss', 'includes') ]
    }))
    .pipe(autoprefixer('last 2 version'))
    //.pipe(cleancss({compatibility: 'ie8'}))
    .pipe(gulp.dest('style'));
    //.pipe(clean());
});

gulp.task('font', ['removecss'], function() {
  return gulp.src('scss/fontawesome/font-awesome.scss')
      .pipe(scss({
        paths: [ path.join(__dirname, 'scss', 'includes') ]
      }))
      .pipe(cleancss({compatibility: 'ie8'}))
      .pipe(rename({ suffix: '.min' }))
      .pipe(gulp.dest('style'));
});

// Scripts
gulp.task('scripts', ['removejs'], function() {
  return gulp.src('amd/src/*.js') 
    .pipe(jshint('.jshintrc'))
    .pipe(jshint.reporter('default'))
    .pipe(rename({ suffix: '.min' }))
    .pipe(uglify())
    .pipe(gulp.dest('amd/build'));
});

// // Images
// gulp.task('images', function() {
//   return gulp.src('src/images/**/*')
//     .pipe(cache(imagemin({ optimizationLevel: 3, progressive: true, interlaced: true })))
//     .pipe(gulp.dest('dist/images'));
// });

gulp.task('removejs', function() {
  return gulp.src(['amd/build/*.js'], {read: false})
    .pipe(clean());
});
gulp.task('removecss', function() {
  // return gulp.src(['amd/build/*.js'], {read: false})
  //   .pipe(clean());
  return gulp.src(['style/*.css'], {read: false})
        .pipe(clean());
});

// Default task
gulp.task('default',  function() {
  gulp.start( 'scripts', 'styles', 'font');
});


// Watch
gulp.task('watch', function() {

  // Watch .scss files
  gulp.watch('scss/*.scss', ['styles']);

  // Watch .js files
  gulp.watch('**/*.js', ['scripts']);

  // Watch image files
  //gulp.watch('src/images/**/*', ['images']);

  // Create LiveReload server
  livereload.listen();

  // Watch any files in dist/, reload on change
  //gulp.watch(['dist/**']).on('change', livereload.changed);

});