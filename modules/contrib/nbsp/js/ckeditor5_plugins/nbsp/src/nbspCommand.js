import { Command } from "ckeditor5/src/core";

export default class NbspCommand extends Command {
  execute() {
    // Insert tag in the current selection location.
    this.editor.model.change((writer) => {
      const nbspElement = "<nbsp>&nbsp;</nbsp>";
      const nbspViewFragment = this.editor.data.processor.toView(nbspElement);
      const nbspModelFragment = this.editor.data.toModel(nbspViewFragment);
      this.editor.model.insertContent(nbspModelFragment);
    });
  }
}
