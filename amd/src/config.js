define([], function () {

    window.requirejs.config({
        paths: {
          'bcpopup':    M.cfg.wwwroot + '/blocks/gradetracker/js/lib/jquery-bc-popup/jquery-bc-popup',
          'bcnotify':   M.cfg.wwwroot + '/blocks/gradetracker/js/lib/jquery-bc-notify/jquery-bc-notify'
        },
        shim: {
          'bcpopup':    {exports: 'bcPopUp'},
          'bcnotify':    {exports: 'bcNotify'}
        }
    });

});