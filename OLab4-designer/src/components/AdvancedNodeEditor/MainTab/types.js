// @flow
export type MainTabProps = {
  text: string,
  title: string,
  type: number,
  isEnd: boolean,
  isRootNode: boolean,
  isVisitOnce: boolean,
  handleKeyDown: Function,
  handleTitleChange: Function,
  handleEditorChange: Function,
  handleVisitOnceChange: Function,
  handleCheckBoxChange: Function,
};
