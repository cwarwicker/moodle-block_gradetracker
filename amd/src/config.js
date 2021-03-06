define([], function () {

    window.requirejs.config({
        paths: {
          'bcpopup':      M.cfg.wwwroot + '/blocks/gradetracker/js/lib/jquery-bc-popup/jquery-bc-popup',
          'bcnotify':     M.cfg.wwwroot + '/blocks/gradetracker/js/lib/jquery-bc-notify/jquery-bc-notify',
          'freezetable':  M.cfg.wwwroot + '/blocks/gradetracker/js/lib/jquery-freeze-table/freeze-table',
          'slimmenu':     M.cfg.wwwroot + '/blocks/gradetracker/js/lib/jquery-slimmenu/jquery.slimmenu.min'
        },
        shim: {
          'bcpopup':      {exports: 'bcPopUp'},
          'bcnotify':     {exports: 'bcNotify'},
          'freezetable':  {exports: 'freezeTable'},
          'slimmenu':     {exports: 'slimmenu'}
        }
    });

});