// @flow
export type EditorOptions = {
  [props: string]: any,
};

export type TextEditorProps = {
  text: string,
  editorId?: string,
  width: number,
  height: number,
  handleKeyDown: Function,
  handleEditorChange: Function,
  editorOptions?: EditorOptions,
};
