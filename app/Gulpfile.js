"use strict";

const gulp = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const concat = require('gulp-concat');
const uglify = require('gulp-uglify');
const clean_css = require('gulp-clean-css');
const sourcemaps = require('gulp-sourcemaps');
const replace = require('gulp-replace');

const paths = {
    bootstrap: {
        styles: {
            src: [
                'node_modules/bootstrap/dist/css/bootstrap.min.css',
                'node_modules/bootstrap-icons/font/bootstrap-icons.min.css'
            ],
            dest: 'public/dist/styles'
        },
        scripts: {
            src: [
                'node_modules/bootstrap/dist/js/bootstrap.bundle.min.js',
            ],
            dest: 'public/dist/scripts'
        },
        fonts: {
            src: 'node_modules/bootstrap-icons/font/fonts/*',
            dest: 'public/dist/fonts'
        }
    },
    chart: {
        scripts: {
            src: [
                'node_modules/chart.js/dist/chart.umd.js',
                'node_modules/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js'
            ],
            dest: 'public/dist/scripts'
        }
    },
    ldloader: {
        styles: {
            src: [
                'node_modules/ldloader/index.min.css'
            ],
            dest: 'public/dist/styles'
        },
        scripts: {
            src: [
                'node_modules/ldloader/index.min.js'
            ],
            dest: 'public/dist/scripts'
        }
    }
};

function bootstrapCSS() {
    return gulp.src(paths.bootstrap.styles.src)
        .pipe(sourcemaps.init())
        .pipe(replace('url("fonts/', 'url("/dist/fonts/'))
        .pipe(concat('bootstrap.min.css'))
        .pipe(gulp.dest(paths.bootstrap.styles.dest))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest(paths.bootstrap.styles.dest))
}

function bootstrapJS() {
    return gulp.src(paths.bootstrap.scripts.src)
        .pipe(sourcemaps.init())
        .pipe(concat('bootstrap.min.js'))
        .pipe(gulp.dest(paths.bootstrap.scripts.dest))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest(paths.bootstrap.scripts.dest))
}

function chartJS() {
    return gulp.src(paths.chart.scripts.src)
        .pipe(sourcemaps.init())
        .pipe(concat('chart.min.js'))
        .pipe(gulp.dest(paths.chart.scripts.dest))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest(paths.chart.scripts.dest))
}

function ldloaderJS() {
    return gulp.src(paths.ldloader.scripts.src)
        .pipe(sourcemaps.init())
        .pipe(concat('ldloader.min.js'))
        .pipe(gulp.dest(paths.ldloader.scripts.dest))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest(paths.ldloader.scripts.dest))
}

function ldloaderCSS() {
    return gulp.src(paths.ldloader.styles.src)
        .pipe(sourcemaps.init())
        .pipe(concat('ldloader.min.css'))
        .pipe(gulp.dest(paths.ldloader.styles.dest))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest(paths.ldloader.styles.dest))
}

function bootstrapFonts() {
    return gulp.src(paths.bootstrap.fonts.src)
        .pipe(gulp.dest(paths.bootstrap.fonts.dest))
}

function watch() {
    gulp.watch(paths.bootstrap.styles.src, bootstrapCSS);
    gulp.watch(paths.bootstrap.scripts.src, bootstrapJS);
    gulp.watch(paths.bootstrap.fonts.src, bootstrapFonts);
    gulp.watch(paths.chart.scripts.src, chartJS);
    gulp.watch(paths.ldloader.scripts.src, ldloaderJS);
    gulp.watch(paths.ldloader.styles.src, ldloaderCSS);
}

exports.default = gulp.series(
    gulp.parallel(bootstrapCSS, bootstrapJS, bootstrapFonts, chartJS, ldloaderCSS, ldloaderJS),
    watch
);