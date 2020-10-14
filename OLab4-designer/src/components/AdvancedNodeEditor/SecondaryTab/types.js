// @flow
export type SecondaryTabProps = {
  classes: {
    [prop: string]: any,
  },
  info: string,
  annotation: string,
  nodeId: number,
  linkStyle: number,
  priorityId: number,
  handleKeyDown: Function,
  handleSelectChange: Function,
  handleEditorChange: Function,
};
