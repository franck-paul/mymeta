/*global $, dotclear, metaEditor */
'use strict';

dotclear.ready(() => {
  const mymeta = dotclear.getData('mymeta');
  dotclear.msg.confirm_tag_delete = mymeta.msg;
  $('#tag_delete').on('submit', () => window.confirm(dotclear.msg.confirm_tag_delete));
});
