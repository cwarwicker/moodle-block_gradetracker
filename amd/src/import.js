define(['jquery', 'jqueryui'], function($, ui) {

  var config = {};

  config.init = function(){

    // Bind elements
    config.bindings();

  }

  config.bindings = function(){

    $('#uploadBtn').off('change');
    $('#uploadBtn').on('change', function(){
         $('#uploadFile').val( $('#uploadBtn').val() );
    });

    $('.class').off('click');
    $('.class').on('click', function(e){



      e.preventDefault();

    });

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

    client.log('Loaded import.js');

  }

  // Return client object
  return client;


});