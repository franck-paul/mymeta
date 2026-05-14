/*global $, dotclear */
'use strict';

dotclear.ready(() => {
  const mymeta = dotclear.getData('mymeta');
  dotclear.msg.confirm_tag_delete = mymeta.msg;
  $('#tag_delete').on('submit', () => globalThis.confirm(dotclear.msg.confirm_tag_delete));
});
