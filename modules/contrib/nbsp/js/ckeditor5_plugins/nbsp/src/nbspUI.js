import { Plugin } from "ckeditor5/src/core";
import { ButtonView } from "ckeditor5/src/ui";
import nbspIcon from "../../../../icons/nbsp.svg";

/**
 * The Non-breaking space plugin.
 *
 * @internal
 */
export default class NbspUI extends Plugin {
  init() {
    const editor = this.editor;
    editor.ui.componentFactory.add("nbsp", (locale) => {
      const view = new ButtonView(locale);

      view.set({
        label: "Insert non-breaking space",
        icon: nbspIcon,
        tooltip: true,
      });

      // Callback executed once the plugin button is clicked.
      view.on("execute", () => {
        editor.model.change((writer) => {
          editor.commands.execute("nbsp");
        });
      });

      return view;
    });
  }
}
