// Put this file in path/to/plugin/amd/src
// You can call it anything you like

define(['jquery'], function($) {

    var GT = {};
    GT.value = 123;

    GT.function = function(vl){
      console.log('GT function(): ' + vl + ' ' + GT.value);
    };

    window.GT = GT;



    var client = {};

    client.another = function(){
      console.log('ztest.js another: ' + GT.value);
    }


    client.init = function() {
      console.log('ztest.js init: ' + GT.value);
      client.another();
      GT.function('ztest.js');
    }

    return client;

});