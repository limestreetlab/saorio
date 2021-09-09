
/*
TempusDominus datepicker settings
*/
$("document").ready( function() {
  var now = new Date();
  var lastYear = now.getFullYear() - 1; //a year ago
  var genesisYear = lastYear - 60; //when the world begins
      
  const picker = new tempusDominus.TempusDominus(document.querySelector('#datepicker'), {
    display: {
      viewMode: 'years',
      components: {
        decades: false,
        year: true,
        month: true,
        date: true,
        hours: false,
        minutes: false,
        seconds: false,
      }
    }, //end display options
    restrictions: {
      minDate: new Date(genesisYear, 0, 1),
      maxDate: new Date(lastYear, 11, 31), 
    }, //end restrictions options
    useCurrent: false,  
    //defaultDate: new Date("2019-12-31"),
  });

});
