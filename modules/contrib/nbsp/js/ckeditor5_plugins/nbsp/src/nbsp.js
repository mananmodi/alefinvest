import { Plugin } from "ckeditor5/src/core";
import NbspEditing from "./nbspEditing";
import NbspUI from "./nbspUI";

/**
 * The Non-breaking space plugin.
 *
 * @internal
 */
export default class Nbsp extends Plugin {
  /**
   * @inheritdoc
   */
  static get pluginName() {
    return "Nbsp";
  }

  static get requires() {
    return [NbspEditing, NbspUI];
  }
}
