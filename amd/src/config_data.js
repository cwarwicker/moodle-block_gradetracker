define(['jquery', 'jqueryui'], function($, ui) {

  var config = {};

  config.init = function(){

    // Bind elements
    config.bindings();

  }

  config.bindings = function(){

    

  }




  var client = {};

  //-- Log something to console
  client.log = function(log){
      console.log('[GT] ' + new Date().toTimeString().split(' ')[0] + ': ' + log );
  }

  //-- Initialise the scripts
  client.init = function() {

    // Bindings
    config.init();

    client.log('Loaded config_data.js');

  }

  // Return client object
  return client;


});