var elixir = require('laravel-elixir');

elixir.config.publicPath = "./";
elixir.config.css.outputFolder = "";

elixir(function (mix) {
    mix.sass('style.scss');
});
