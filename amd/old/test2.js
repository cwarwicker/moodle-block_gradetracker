define(['jquery'], function($) {

    var test2 = {};

    test2.test = function(){
      console.log('test2.js test(): ' + GT.value);
    }

    test2.init = function() {
        console.log('test2.js init(): ' + GT.value);
        test2.test();
        GT.function('test2.js');
    }

    return test2;

});