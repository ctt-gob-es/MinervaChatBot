/******/ (() => { // webpackBootstrap
var __webpack_exports__ = {};
/*!**************************************************!*\
  !*** ./resources/assets/js/custom-datatables.js ***!
  \**************************************************/
$.extend($.fn.dataTable.defaults, {
  'paging': true,
  'info': true,
  'ordering': true,
  'autoWidth': false,
  'pageLength': 10,
  'language': {
    'search': '',
    'sSearch': 'Search'
  },
  'preDrawCallback': function preDrawCallback() {
    customSearch();
  }
});
function customSearch() {
  $('.dataTables_filter input').addClass('form-control');
  $('.dataTables_filter input').attr('placeholder', Lang.get('messages.search'));
}
/******/ })()
;