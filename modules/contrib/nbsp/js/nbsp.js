/**
 * @file insert Non-Breaking Space for CKEditor
 * Copyright (C) 2016 Kevin Wenger of Antistatique
 * Create a command and enable the Ctrl+Space shortcut
 * to insert a non-breaking space in CKEditor
 * Also add a non-breaking space button
 *
 * This is the CKEditor 4 version of the plugin.
 */

/* global jQuery Drupal CKEDITOR */

(function ($, Drupal, CKEDITOR) {
  "use strict";

  CKEDITOR.plugins.add("nbsp", {
    icons: "nbsp",
    hidpi: true,

    init: function (editor) {
      //Add &shy; widget
      editor.widgets.add("insertNbsp", {
        template: "<nbsp>&nbsp;</nbsp>",
        draggable: false,
        allowedContent: "nbsp",
        requiredContent: new CKEDITOR.style({
          element: "nbsp",
        }),
        inline: true,

        //position cursor after widget so users can keep on typing
        init: function () {
          this.once(
            "focus",
            function () {
              var range = editor.createRange();
              range.moveToPosition(this.wrapper, CKEDITOR.POSITION_AFTER_END);
              range.select();
            },
            this,
          );
        },
        upcast: function (element, data) {
          return element.name === "nbsp";
        },
      });

      // Insert  if Ctrl+Space is pressed:
      editor.setKeystroke(CKEDITOR.CTRL + 32 /* space */, "insertNbsp");

      // Register the toolbar button.
      if (editor.ui.addButton) {
        editor.ui.addButton("DrupalNbsp", {
          label: Drupal.t("Non-breaking space"),
          command: "insertNbsp",
          icon: editor.config.NbspImageIcon,
        });
      }
    },
  });
})(jQuery, Drupal, CKEDITOR);
