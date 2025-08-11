import { Plugin } from "ckeditor5/src/core";
import NbspCommand from "./nbspCommand";

/**
 * The Non-breaking space plugin.
 *
 * @internal
 */
export default class NbspEditing extends Plugin {
  _defineSchema() {
    const schema = this.editor.model.schema;
    schema.register("nbsp", {
      allowWhere: "$text",
      allowAttributesOf: "$text",
      isInline: true,
      isObject: true,
    });
  }

  _defineConverters() {
    const conversion = this.editor.conversion;
    conversion.elementToElement({ model: "nbsp", view: "nbsp" });
  }

  init() {
    const editor = this.editor;
    this.editor.commands.add("nbsp", new NbspCommand(this.editor));
    this._defineSchema();
    this._defineConverters();

    // Insert if Ctrl+Space is pressed:
    editor.keystrokes.set(["ctrl", 32], (data, cancel) => {
      editor.commands.execute("nbsp");
      // Prevent the default (native) action and stop the underlying keydown
      // event so no other editor feature will interfere.
      cancel();
    });
  }
}
